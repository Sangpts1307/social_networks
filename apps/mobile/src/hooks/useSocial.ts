/* eslint-disable react-hooks/set-state-in-effect -- Synchronize the view with Gateway requests when filters change. */
import { useCallback, useEffect, useRef, useState } from "react";
import type {
  MediaAttachment,
  Story,
  Channel,
  Comment,
  Post,
  Reaction,
  SocialFilter,
  User,
} from "../types";
import { postsApi } from "../services/posts";
import {
  initialPosts,
  initialComments,
  socialChannels,
} from "../services/social-demo";
export type SocialTab =
  | "feed"
  | "channels"
  | "saved"
  | "profile"
  | "messages"
  | "notifications"
  | "menu";
export function useSocial(token: string, user: User, demo: boolean) {
  const [stories, setStories] = useState<Story[]>(() =>
    demo
      ? [
          {
            id: "sample-story",
            author_user_id: "demo-linh",
            body: "Chào mừng thành viên mới! 👋",
            media: [
              {
                id: "sample-story-photo",
                type: "image",
                uri: "https://images.unsplash.com/photo-1522071820081-009f0129c71c?w=800&auto=format&fit=crop&q=80",
              },
            ],
            background: "#1877f2",
            created_at: new Date().toISOString(),
            expires_at: new Date(Date.now() + 86400000).toISOString(),
          },
        ]
      : [],
  );
  const [clock, setClock] = useState(Date.now);
  useEffect(() => {
    const timer = setInterval(() => setClock(Date.now()), 30000);
    return () => clearInterval(timer);
  }, []);
  const [tab, setTab] = useState<SocialTab>("feed");
  const [channel, setChannel] = useState("");
  const [search, setSearch] = useState("");
  const [posts, setPosts] = useState<Post[]>(() =>
    demo ? initialPosts() : [],
  );
  const [channels, setChannels] = useState<Channel[]>(() =>
    demo ? socialChannels : [],
  );
  const [comments, setComments] = useState<Record<string, Comment[]>>(() =>
    demo ? initialComments() : {},
  );
  const [joined, setJoined] = useState<string[]>(
    demo ? ["general", "tech", "life"] : [],
  );
  const [cursor, setCursor] = useState<string | null>(null);
  const [loading, setLoading] = useState(!demo);
  const [error, setError] = useState("");
  const [toast, setToast] = useState("");
  const [busy, setBusy] = useState<string[]>([]);
  const locks = useRef(new Set<string>());
  const sequence = useRef(0);
  const alive = useRef(true);
  const filter: SocialFilter = {
    ...(channel ? { channel_id: channel } : {}),
    ...(tab === "saved" ? { saved: "1" as const } : {}),
    ...(tab === "profile" ? { author_user_id: user.id } : {}),
  };
  const filterKey = JSON.stringify(filter);
  const load = useCallback(
    async (next?: string) => {
      if (demo) return;
      const current = ++sequence.current;
      setLoading(true);
      setError("");
      try {
        const result = await postsApi.list(token, JSON.parse(filterKey), next);
        if (!alive.current || current !== sequence.current) return;
        setPosts((previous) =>
          next
            ? [
                ...previous,
                ...result.data.filter(
                  (p) => !previous.some((old) => old.id === p.id),
                ),
              ]
            : result.data,
        );
        setCursor(result.meta.next_cursor);
      } catch (e) {
        if (alive.current && current === sequence.current)
          setError(e instanceof Error ? e.message : "Không tải được bảng tin.");
      } finally {
        if (alive.current && current === sequence.current) setLoading(false);
      }
    },
    [demo, token, filterKey],
  );
  useEffect(() => {
    alive.current = true;
    return () => {
      alive.current = false;
    };
  }, []);
  useEffect(() => {
    if (!demo) {
      setPosts([]);
      setCursor(null);
      void load();
    }
  }, [demo, load]);
  useEffect(() => {
    if (demo) return;
    let active = true;
    async function fetchChannels() {
      const all: Channel[] = [];
      let next: string | undefined;
      do {
        const page = await postsApi.channels(token, next);
        all.push(...page.data);
        next = page.meta.next_cursor || undefined;
      } while (next && active);
      if (active) setChannels(all);
    }
    void fetchChannels().catch((e) => {
      if (active)
        setError(e instanceof Error ? e.message : "Không tải được kênh.");
    });
    return () => {
      active = false;
    };
  }, [demo, token]);
  useEffect(() => {
    if (!toast) return;
    const timer = setTimeout(() => setToast(""), 3500);
    return () => clearTimeout(timer);
  }, [toast]);
  async function mutate(key: string, action: () => Promise<void>) {
    if (locks.current.has(key)) return;
    locks.current.add(key);
    setBusy((items) => [...items, key]);
    setError("");
    try {
      await action();
    } catch (e) {
      if (alive.current)
        setError(e instanceof Error ? e.message : "Thao tác chưa thành công.");
      throw e;
    } finally {
      locks.current.delete(key);
      if (alive.current) setBusy((items) => items.filter((id) => id !== key));
    }
  }
  async function react(post: Post, reaction: Reaction | null) {
    await mutate(`reaction:${post.id}`, async () => {
      if (demo && !joined.includes(post.channel_id))
        throw new Error("Hãy tham gia kênh trước khi thả cảm xúc.");
      if (!demo) await postsApi.react(token, post.id, reaction);
      setPosts((items) =>
        items.map((p) =>
          p.id === post.id
            ? {
                ...p,
                my_reaction: reaction,
                reaction_count: Math.max(
                  0,
                  Number(p.reaction_count) +
                    (reaction && !p.my_reaction
                      ? 1
                      : !reaction && p.my_reaction
                        ? -1
                        : 0),
                ),
              }
            : p,
        ),
      );
    });
  }
  async function save(post: Post) {
    await mutate(`save:${post.id}`, async () => {
      const saved = !post.is_saved;
      if (!demo) await postsApi.save(token, post.id, saved);
      setPosts((items) =>
        items.map((p) => (p.id === post.id ? { ...p, is_saved: saved } : p)),
      );
      setToast(saved ? "Đã thêm vào bài viết đã lưu" : "Đã bỏ lưu bài viết");
    });
  }
  async function publishStory(
    body: string,
    media: MediaAttachment[],
    background: string,
  ) {
    if (!demo) throw new Error("Story chưa kết nối máy chủ.");
    if (!body.trim() && !media.length)
      throw new Error("Hãy thêm chữ, ảnh hoặc video.");
    const now = Date.now();
    setStories((items) => [
      {
        id: `story-${now}`,
        author_user_id: user.id,
        body: body.trim(),
        media,
        background,
        created_at: new Date(now).toISOString(),
        expires_at: new Date(now + 86400000).toISOString(),
      },
      ...items,
    ]);
    setTab("feed");
    setToast("Đã chia sẻ tin · Hiển thị trong 24 giờ");
  }
  async function publish(
    body: string,
    channelId: string,
    editing?: Post,
    media: MediaAttachment[] = [],
  ) {
    if (!body.trim() && !media.length)
      throw new Error("Hãy thêm nội dung, ảnh hoặc video.");
    if (!demo && media.length)
      throw new Error("Tải ảnh/video lên máy chủ chưa được kết nối.");
    if (demo && !joined.includes(channelId))
      throw new Error("Hãy tham gia kênh trước khi đăng hoặc sửa bài viết.");
    if (editing) {
      let updated: Post = {
        ...editing,
        demo_media: media,
        body_plaintext: body.trim(),
        edited_at: new Date().toISOString(),
      };
      if (!demo)
        updated = {
          ...editing,
          ...(await postsApi.update(token, editing.id, body.trim())).data,
        };
      setPosts((items) =>
        items.map((p) => (p.id === editing.id ? updated : p)),
      );
      setToast("Đã cập nhật bài viết");
      return;
    }
    let post: Post = {
      demo_media: media,
      id: `local-${Date.now()}-${Math.random().toString(36).slice(2, 7)}`,
      channel_id: channelId,
      author_user_id: user.id,
      title: null,
      body_plaintext: body.trim(),
      published_at: new Date().toISOString(),
      reaction_count: 0,
      comment_count: 0,
      my_reaction: null,
      is_saved: false,
      post_type: "update",
    };
    if (!demo)
      post = {
        ...post,
        ...(await postsApi.create(token, channelId, body.trim())).data,
      };
    setPosts((items) => [post, ...items]);
    setToast("Bài viết của bạn đã được chia sẻ");
  }
  async function remove(post: Post) {
    await mutate(`delete:${post.id}`, async () => {
      if (!demo) await postsApi.remove(token, post.id);
      setPosts((items) => items.filter((p) => p.id !== post.id));
      setToast("Đã xóa bài viết");
    });
  }
  async function addComment(
    postId: string,
    body: string,
    parentId?: string,
  ): Promise<Comment> {
    if (!body.trim()) throw new Error("Vui lòng nhập bình luận.");
    const post = posts.find((item) => item.id === postId);
    if (demo && post && !joined.includes(post.channel_id))
      throw new Error("Hãy tham gia kênh trước khi bình luận.");
    let comment: Comment = {
      id: `local-comment-${Date.now()}`,
      post_id: postId,
      parent_comment_id: parentId || null,
      author_user_id: user.id,
      body_plaintext: body.trim(),
      created_at: new Date().toISOString(),
    };
    if (!demo)
      comment = (await postsApi.comment(token, postId, body.trim(), parentId))
        .data;
    setComments((current) => ({
      ...current,
      [postId]: [...(current[postId] || []), comment],
    }));
    setPosts((items) =>
      items.map((p) =>
        p.id === postId
          ? { ...p, comment_count: Number(p.comment_count) + 1 }
          : p,
      ),
    );
    return comment;
  }
  const filtered = posts.filter(
    (post) =>
      (!channel || post.channel_id === channel) &&
      (tab !== "saved" || !!post.is_saved) &&
      (tab !== "profile" || post.author_user_id === user.id) &&
      `${post.title || ""} ${post.body_plaintext}`
        .toLocaleLowerCase("vi")
        .includes(search.trim().toLocaleLowerCase("vi")),
  );
  function navigate(next: SocialTab) {
    setTab(next);
    setChannel("");
    setSearch("");
    setError("");
  }
  function openChannel(id: string) {
    setTab("feed");
    setChannel(id);
    setSearch("");
  }
  function toggleJoin(id: string) {
    setJoined((items) =>
      items.includes(id) ? items.filter((v) => v !== id) : [...items, id],
    );
    setToast(
      joined.includes(id)
        ? "Đã rời kênh trong bản demo"
        : "Đã tham gia kênh trong bản demo",
    );
  }
  return {
    tab,
    stories: stories.filter(
      (story) => new Date(story.expires_at).getTime() > clock,
    ),
    publishStory,
    navigate,
    channel,
    setChannel,
    search,
    setSearch,
    posts: filtered,
    allPosts: posts,
    channels,
    comments,
    joined,
    toggleJoin,
    openChannel,
    cursor,
    load,
    loading,
    error,
    setError,
    toast,
    busy,
    react,
    save,
    publish,
    remove,
    addComment,
  };
}
