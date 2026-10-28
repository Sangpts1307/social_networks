import { useState } from "react";
import { Image, Pressable, Text, View } from "react-native";
import type { Post, User, Reaction } from "../types";
import { MediaPreview } from "./MediaPreview";
import { Icon } from "./ui";
import { memberFor, SocialAvatar, relativeTime } from "./social-ui";
export const reactions: { id: Reaction; emoji: string; label: string }[] = [
  { id: "like", emoji: "👍", label: "Thích" },
  { id: "love", emoji: "❤️", label: "Yêu thích" },
  { id: "sad", emoji: "😢", label: "Buồn" },
  { id: "angry", emoji: "😠", label: "Phẫn nộ" },
];
export function PostCard({
  post,
  user,
  demo,
  channelName,
  busy,
  onReact,
  onSave,
  onComments,
  onMenu,
}: {
  post: Post;
  user: User;
  demo: boolean;
  channelName: string;
  busy: boolean;
  onReact: (reaction: Reaction | null) => void;
  onSave: () => void;
  onComments: () => void;
  onMenu: () => void;
}) {
  const [expanded, setExpanded] = useState(false);
  const [imageFailed, setImageFailed] = useState(false);
  const [choosing, setChoosing] = useState(false);
  const member = memberFor(post.author_user_id, user, demo);
  const reaction = reactions.find((r) => r.id === post.my_reaction);
  const long = post.body_plaintext.length > 320;
  return (
    <View className="bg-white border-b border-slate-200 mb-2 overflow-hidden">
      <View className="px-4 pt-4 flex-row items-center gap-3">
        <SocialAvatar member={member} />
        <View className="flex-1">
          <Text className="font-bold text-slate-900">{member.name}</Text>
          <Text className="text-xs text-slate-400 mt-1">
            {relativeTime(post.published_at)}
            {post.edited_at ? " · Đã chỉnh sửa" : ""} · {channelName}
          </Text>
        </View>
        <Pressable
          accessibilityRole="button"
          accessibilityLabel={`Tùy chọn bài viết của ${member.name}`}
          onPress={onMenu}
          className="p-2"
        >
          <Icon name="ellipsis-horizontal" size={20} />
        </Pressable>
      </View>
      {post.post_type === "announcement" && (
        <View className="mx-4 mt-4 flex-row items-center gap-1.5">
          <Icon name="megaphone-outline" color="#1877f2" size={14} />
          <Text className="text-blue-600 text-xs font-semibold">
            THÔNG BÁO NỘI BỘ
          </Text>
        </View>
      )}
      {!!post.title && (
        <Text className="px-4 text-lg leading-7 font-bold text-slate-900 mt-3">
          {post.title}
        </Text>
      )}
      <Text
        className="px-4 text-slate-700 text-base leading-7 mt-3"
        numberOfLines={long && !expanded ? 5 : undefined}
        selectable
      >
        {post.body_plaintext}
      </Text>
      {long && (
        <Pressable
          accessibilityRole="button"
          onPress={() => setExpanded((v) => !v)}
          className="px-4 py-2"
        >
          <Text className="text-blue-600 font-semibold">
            {expanded ? "Thu gọn" : "Xem thêm"}
          </Text>
        </Pressable>
      )}
      {post.demo_media?.map((media) => (
        <View key={media.id} className="mt-2">
          <MediaPreview media={media} />
        </View>
      ))}
      {demo && !!post.demo_image_url && !imageFailed && (
        <Image
          source={{ uri: post.demo_image_url }}
          onError={() => setImageFailed(true)}
          accessibilityLabel="Ảnh minh họa bài viết từ bản thiết kế web"
          resizeMode="cover"
          style={{ width: "100%", aspectRatio: 1.65, marginTop: 14 }}
        />
      )}
      <View className="px-4 flex-row items-center justify-between mt-4 mb-3">
        <Text className="text-xs text-slate-500">
          {Number(post.reaction_count) > 0
            ? `👍 ${post.reaction_count} cảm xúc`
            : "Hãy là người đầu tiên thả cảm xúc"}
        </Text>
        <Pressable
          accessibilityRole="button"
          accessibilityLabel={`Xem ${post.comment_count} bình luận`}
          onPress={onComments}
        >
          <Text className="text-xs text-slate-500">
            {post.comment_count} bình luận
          </Text>
        </Pressable>
      </View>
      {choosing && (
        <View className="mx-4 mb-3 p-2 rounded-2xl bg-blue-50 flex-row justify-around">
          {reactions.map((item) => (
            <Pressable
              accessibilityRole="button"
              accessibilityLabel={item.label}
              key={item.id}
              disabled={busy}
              onPress={() => {
                onReact(item.id);
                setChoosing(false);
              }}
              className="items-center px-2 py-1"
            >
              <Text className="text-2xl">{item.emoji}</Text>
              <Text className="text-xs text-slate-500 mt-1">{item.label}</Text>
            </Pressable>
          ))}
        </View>
      )}
      <View className="border-t border-slate-100 flex-row px-2 py-1">
        <Pressable
          accessibilityRole="button"
          accessibilityLabel={reaction ? "Bỏ cảm xúc" : "Thích bài viết"}
          accessibilityState={{ selected: !!reaction, disabled: busy }}
          disabled={busy}
          onPress={() => onReact(reaction ? null : "like")}
          onLongPress={() => setChoosing((v) => !v)}
          className="flex-1 flex-row justify-center items-center gap-1.5 py-3"
        >
          {reaction ? (
            <Text>{reaction.emoji}</Text>
          ) : (
            <Icon name="thumbs-up-outline" size={18} />
          )}
          <Text
            className={`text-xs font-semibold ${reaction ? "text-blue-600" : "text-slate-500"}`}
          >
            {reaction?.label || "Thích"}
          </Text>
        </Pressable>
        <Pressable
          accessibilityRole="button"
          accessibilityLabel="Chọn cảm xúc"
          disabled={busy}
          onPress={() => setChoosing((v) => !v)}
          className="px-1 justify-center"
        >
          <Icon name="chevron-down" size={14} />
        </Pressable>
        <Pressable
          accessibilityRole="button"
          accessibilityLabel="Bình luận bài viết"
          onPress={onComments}
          className="flex-1 flex-row justify-center items-center gap-1.5 py-3"
        >
          <Icon name="chatbubble-outline" size={18} />
          <Text className="text-xs text-slate-500 font-semibold">
            Bình luận
          </Text>
        </Pressable>
        <Pressable
          accessibilityRole="button"
          accessibilityLabel={
            post.is_saved ? "Bỏ lưu bài viết" : "Lưu bài viết"
          }
          accessibilityState={{ selected: !!post.is_saved, disabled: busy }}
          disabled={busy}
          onPress={onSave}
          className="flex-1 flex-row justify-center items-center gap-1.5 py-3"
        >
          <Icon
            name={post.is_saved ? "bookmark" : "bookmark-outline"}
            size={18}
            color={post.is_saved ? "#1877f2" : "#64748b"}
          />
          <Text
            className={`text-xs font-semibold ${post.is_saved ? "text-blue-600" : "text-slate-500"}`}
          >
            {post.is_saved ? "Đã lưu" : "Lưu"}
          </Text>
        </Pressable>
      </View>
    </View>
  );
}
