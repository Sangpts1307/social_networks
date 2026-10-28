/* eslint-disable react-hooks/set-state-in-effect -- Load comments for the opened post from the external API. */
import {
  useCallback,
  useEffect,
  useRef,
  useState,
  type ComponentRef,
} from "react";
import {
  ActivityIndicator,
  FlatList,
  Pressable,
  Text,
  TextInput,
  View,
} from "react-native";
import type { Comment, Post, User } from "../types";
import { postsApi } from "../services/posts";
import { Button, Icon, Notice } from "./ui";
import {
  Sheet,
  SocialAvatar,
  memberFor,
  relativeTime,
  EmptyState,
} from "./social-ui";
export function CommentsSheet({
  post,
  user,
  demo,
  token,
  initial,
  onClose,
  onAdd,
}: {
  post: Post;
  user: User;
  demo: boolean;
  token: string;
  initial: Comment[];
  onClose: () => void;
  onAdd: (postId: string, body: string, parentId?: string) => Promise<Comment>;
}) {
  const [comments, setComments] = useState(initial);
  const [cursor, setCursor] = useState<string | null>(null);
  const [loading, setLoading] = useState(!demo);
  const [busy, setBusy] = useState(false);
  const [body, setBody] = useState("");
  const [reply, setReply] = useState<Comment | null>(null);
  const [error, setError] = useState("");
  const active = useRef(true);
  const loadLock = useRef(false);
  const input = useRef<ComponentRef<typeof TextInput>>(null);
  const load = useCallback(
    async (next?: string) => {
      if (demo || loadLock.current) return;
      loadLock.current = true;
      setLoading(true);
      setError("");
      try {
        const page = await postsApi.comments(token, post.id, next);
        if (active.current) {
          setComments((current) =>
            next
              ? [
                  ...current,
                  ...page.data.filter(
                    (c) => !current.some((p) => p.id === c.id),
                  ),
                ]
              : page.data,
          );
          setCursor(page.meta.next_cursor);
        }
      } catch (e) {
        if (active.current)
          setError(
            e instanceof Error ? e.message : "Không tải được bình luận.",
          );
      } finally {
        loadLock.current = false;
        if (active.current) setLoading(false);
      }
    },
    [demo, token, post.id],
  );
  useEffect(() => {
    active.current = true;
    void load();
    return () => {
      active.current = false;
    };
  }, [load]);
  async function send() {
    if (!body.trim() || busy) return;
    setBusy(true);
    setError("");
    try {
      const comment = await onAdd(post.id, body, reply?.id);
      setComments((current) => [...current, comment]);
      setBody("");
      setReply(null);
    } catch (e) {
      setError(e instanceof Error ? e.message : "Không gửi được bình luận.");
    } finally {
      setBusy(false);
    }
  }
  const ordered: Comment[] = [];
  comments
    .filter((c) => !c.parent_comment_id)
    .forEach((root) => {
      ordered.push(
        root,
        ...comments.filter((c) => c.parent_comment_id === root.id),
      );
    });
  comments
    .filter(
      (c) =>
        c.parent_comment_id &&
        !comments.some((root) => root.id === c.parent_comment_id),
    )
    .forEach((c) => ordered.push(c));
  return (
    <Sheet title="Bình luận" visible onClose={onClose} disabled={busy}>
      <FlatList
        data={ordered}
        keyExtractor={(c) => c.id}
        keyboardShouldPersistTaps="handled"
        contentContainerStyle={{ padding: 20, paddingBottom: 8 }}
        ListHeaderComponent={
          <View className="mb-4">
            <Text className="text-slate-600 leading-6" numberOfLines={3}>
              {post.title || post.body_plaintext}
            </Text>
            {!!error && <Notice message={error} retry={() => void load()} />}
          </View>
        }
        renderItem={({ item }) => {
          const member = memberFor(item.author_user_id, user, demo);
          return (
            <View
              className={`flex-row gap-2 mb-4 ${item.parent_comment_id ? "ml-8" : ""}`}
            >
              <SocialAvatar member={member} />
              <View className="flex-1">
                <View className="rounded-2xl bg-white border border-slate-100 p-3">
                  <Text className="font-bold text-slate-900 text-sm">
                    {member.name}
                  </Text>
                  <Text
                    className="text-slate-600 text-sm leading-6 mt-1"
                    selectable
                  >
                    {item.body_plaintext}
                  </Text>
                </View>
                <View className="flex-row gap-4 mt-2 px-2">
                  <Text className="text-xs text-slate-400">
                    {relativeTime(item.created_at)}
                  </Text>
                  {!item.parent_comment_id && !post.comments_locked_at && (
                    <Pressable
                      accessibilityRole="button"
                      accessibilityLabel={`Trả lời ${member.name}`}
                      onPress={() => {
                        setReply(item);
                        input.current?.focus();
                      }}
                    >
                      <Text className="text-xs font-semibold text-blue-600">
                        Trả lời
                      </Text>
                    </Pressable>
                  )}
                </View>
              </View>
            </View>
          );
        }}
        ListEmptyComponent={
          loading ? (
            <ActivityIndicator color="#1877f2" />
          ) : (
            <EmptyState
              title="Bắt đầu cuộc trò chuyện"
              detail="Một câu hỏi hay hay một lời động viên đều có ý nghĩa."
            />
          )
        }
        ListFooterComponent={
          cursor ? (
            <Button
              title="Tải thêm bình luận"
              busy={loading}
              secondary
              onPress={() => void load(cursor)}
            />
          ) : undefined
        }
      />
      <View className="bg-white border-t border-slate-100 p-4">
        {reply && (
          <View className="flex-row items-center justify-between mb-2">
            <Text className="text-xs text-blue-600 flex-1">
              Đang trả lời {memberFor(reply.author_user_id, user, demo).name}
            </Text>
            <Pressable
              accessibilityRole="button"
              accessibilityLabel="Hủy trả lời"
              onPress={() => setReply(null)}
            >
              <Icon name="close" size={18} />
            </Pressable>
          </View>
        )}
        {post.comments_locked_at ? (
          <Notice message="Bài viết đã khóa bình luận." />
        ) : (
          <View className="flex-row items-end gap-2">
            <TextInput
              ref={input}
              accessibilityLabel="Viết bình luận"
              placeholder={reply ? "Viết câu trả lời..." : "Viết bình luận..."}
              value={body}
              onChangeText={setBody}
              editable={!busy}
              maxLength={10000}
              multiline
              className="flex-1 rounded-2xl bg-slate-50 border border-slate-200 p-3 text-base text-slate-900 max-h-32"
            />
            <Pressable
              accessibilityRole="button"
              accessibilityLabel="Gửi bình luận"
              disabled={!body.trim() || busy}
              onPress={() => void send()}
              className={`p-3 rounded-2xl bg-blue-600 ${!body.trim() || busy ? "opacity-40" : ""}`}
            >
              {busy ? (
                <ActivityIndicator color="white" />
              ) : (
                <Icon name="send" color="white" size={22} />
              )}
            </Pressable>
          </View>
        )}
      </View>
    </Sheet>
  );
}
