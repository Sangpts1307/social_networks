import { useState } from "react";
import { Pressable, ScrollView, Text, TextInput, View } from "react-native";
import type {
  Channel,
  ComposerMode,
  MediaAttachment,
  Post,
  User,
} from "../types";
import { pickMedia } from "../services/media";
import { Button, Icon, Notice } from "./ui";
import { Sheet, memberFor, SocialAvatar } from "./social-ui";
import { MediaPreview } from "./MediaPreview";
const backgrounds = ["#1877f2", "#0f766e", "#b45309", "#be123c", "#334155"];
export function PostComposer({
  user,
  demo,
  channels,
  initialChannel,
  initialMode = "post",
  editing,
  onClose,
  onPublish,
  onStory,
}: {
  user: User;
  demo: boolean;
  channels: Channel[];
  initialChannel?: string;
  initialMode?: ComposerMode;
  editing?: Post;
  onClose: () => void;
  onPublish: (
    body: string,
    channelId: string,
    editing?: Post,
    media?: MediaAttachment[],
  ) => Promise<void>;
  onStory: (
    body: string,
    media: MediaAttachment[],
    background: string,
  ) => Promise<void>;
}) {
  const [mode, setMode] = useState<ComposerMode>(initialMode);
  const [body, setBody] = useState(editing?.body_plaintext || "");
  const [media, setMedia] = useState<MediaAttachment[]>(
    editing?.demo_media || [],
  );
  const [background, setBackground] = useState(backgrounds[0]);
  const [channel, setChannel] = useState(
    editing?.channel_id ||
      (channels.some((c) => c.id === initialChannel)
        ? initialChannel
        : undefined) ||
      channels[0]?.id ||
      "",
  );
  const [busy, setBusy] = useState(false);
  const [picking, setPicking] = useState(false);
  const [error, setError] = useState("");
  const valid =
    (!!body.trim() || media.length > 0) && (mode === "story" || !!channel);
  function changeMode(next: ComposerMode) {
    if (next === "story" && media.length > 1) {
      setError(
        "Story chỉ dùng một ảnh hoặc video. Hãy xóa bớt tệp trước khi chuyển.",
      );
      return;
    }
    setMode(next);
    setError("");
  }
  async function choose() {
    if (picking) return;
    if (!demo) {
      setError(
        "Tải ảnh/video lên máy chủ chưa được kết nối. Hãy vào bản demo để trải nghiệm.",
      );
      return;
    }
    const limit = mode === "story" ? 1 : 10 - media.length;
    if (limit < 1) {
      setError("Mỗi bài viết tối đa 10 ảnh/video.");
      return;
    }
    setPicking(true);
    setError("");
    try {
      const picked = await pickMedia(limit);
      if (picked.length)
        setMedia((items) =>
          mode === "story" ? picked : [...items, ...picked],
        );
    } catch (e) {
      setError(e instanceof Error ? e.message : "Không chọn được tệp.");
    } finally {
      setPicking(false);
    }
  }
  async function submit() {
    if (busy || picking || !valid) return;
    setBusy(true);
    setError("");
    try {
      if (mode === "story") await onStory(body, media, background);
      else await onPublish(body, channel, editing, media);
      onClose();
    } catch (e) {
      setError(e instanceof Error ? e.message : "Chưa đăng được nội dung.");
    } finally {
      setBusy(false);
    }
  }
  return (
    <Sheet
      title={
        editing
          ? "Chỉnh sửa bài viết"
          : mode === "story"
            ? "Tạo tin"
            : "Tạo bài viết"
      }
      visible
      onClose={onClose}
      disabled={busy}
    >
      <ScrollView
        keyboardShouldPersistTaps="handled"
        contentContainerStyle={{ padding: 16 }}
      >
        {!editing && (
          <View className="flex-row bg-slate-100 rounded-xl p-1 mb-5">
            {(
              [
                { id: "post", label: "Bài viết", icon: "reader-outline" },
                {
                  id: "story",
                  label: "Story · Tin 24 giờ",
                  icon: "albums-outline",
                },
              ] as const
            ).map((item) => (
              <Pressable
                key={item.id}
                accessibilityRole="tab"
                accessibilityState={{ selected: mode === item.id }}
                disabled={busy || picking}
                onPress={() => changeMode(item.id)}
                className={`flex-1 flex-row justify-center items-center gap-2 rounded-lg py-3 ${mode === item.id ? "bg-white" : ""}`}
              >
                <Icon
                  name={item.icon}
                  color={mode === item.id ? "#1877f2" : "#64748b"}
                  size={18}
                />
                <Text
                  className={`text-sm font-semibold ${mode === item.id ? "text-blue-600" : "text-slate-500"}`}
                >
                  {item.label}
                </Text>
              </Pressable>
            ))}
          </View>
        )}
        <View className="flex-row items-center gap-3 mb-4">
          <SocialAvatar member={memberFor(user.id, user, demo)} />
          <View>
            <Text className="font-bold text-slate-900">
              {user.display_name}
            </Text>
            <Text className="text-xs text-slate-500 mt-1">
              {mode === "story"
                ? "Tin của bạn · Hiển thị trong 24 giờ"
                : "Chia sẻ với đồng nghiệp trong kênh"}
            </Text>
          </View>
        </View>
        {mode === "post" && (
          <>
            <Text className="text-slate-600 text-sm font-semibold mb-2">
              Đăng vào kênh
            </Text>
            <ScrollView
              horizontal
              showsHorizontalScrollIndicator={false}
              contentContainerStyle={{ gap: 8, paddingBottom: 16 }}
            >
              {channels.map((c) => (
                <Pressable
                  key={c.id}
                  accessibilityRole="button"
                  accessibilityState={{ selected: channel === c.id }}
                  disabled={busy || !!editing || picking}
                  onPress={() => setChannel(c.id)}
                  className={`rounded-full px-4 py-2 ${channel === c.id ? "bg-blue-50" : "bg-slate-100"}`}
                >
                  <Text
                    className={
                      channel === c.id
                        ? "text-blue-600 font-semibold"
                        : "text-slate-500"
                    }
                  >
                    {c.name}
                  </Text>
                </Pressable>
              ))}
            </ScrollView>
            {!channels.length && (
              <Notice message="Hãy tham gia một kênh trước khi đăng bài." />
            )}
          </>
        )}
        <View
          style={
            mode === "story" && !media.length
              ? {
                  backgroundColor: background,
                  borderRadius: 16,
                  minHeight: 260,
                  justifyContent: "center",
                }
              : undefined
          }
        >
          <TextInput
            accessibilityLabel={
              mode === "story" ? "Nội dung Story" : "Nội dung bài viết"
            }
            placeholder={
              mode === "story"
                ? "Chia sẻ một khoảnh khắc..."
                : "Bạn đang nghĩ gì?"
            }
            placeholderTextColor={
              mode === "story" && !media.length ? "#dbeafe" : "#94a3b8"
            }
            value={body}
            onChangeText={setBody}
            multiline
            editable={!busy && !picking}
            textAlignVertical={mode === "story" ? "center" : "top"}
            maxLength={mode === "story" ? 500 : 50000}
            style={
              mode === "story" && !media.length
                ? {
                    color: "white",
                    fontSize: 24,
                    textAlign: "center",
                    fontWeight: "600",
                    padding: 24,
                  }
                : undefined
            }
            className="min-h-32 text-base leading-7 p-3 text-slate-900 web:outline-none"
          />
        </View>
        {mode === "story" && !media.length && (
          <View className="flex-row gap-3 my-4">
            {backgrounds.map((color) => (
              <Pressable
                key={color}
                accessibilityRole="button"
                accessibilityLabel={`Chọn nền Story ${color}`}
                accessibilityState={{ selected: color === background }}
                disabled={busy || picking}
                onPress={() => setBackground(color)}
                style={{
                  backgroundColor: color,
                  width: 34,
                  height: 34,
                  borderRadius: 999,
                  borderWidth: color === background ? 3 : 0,
                  borderColor: "#93c5fd",
                }}
              />
            ))}
          </View>
        )}
        {media.map((item) => (
          <View key={item.id} className="rounded-xl overflow-hidden mb-3">
            <MediaPreview media={item} tall={mode === "story"} />
            <Pressable
              accessibilityRole="button"
              accessibilityLabel={`Xóa tệp ${item.fileName || item.type}`}
              disabled={busy || picking}
              onPress={() =>
                setMedia((items) => items.filter((m) => m.id !== item.id))
              }
              className="absolute top-2 right-2 bg-slate-900 rounded-full p-2"
            >
              <Icon name="close" color="white" size={18} />
            </Pressable>
            <Text className="text-xs text-slate-500 p-2" numberOfLines={1}>
              {item.fileName ||
                (item.type === "video" ? "Video đã chọn" : "Ảnh đã chọn")}
            </Text>
          </View>
        ))}
        <Pressable
          accessibilityRole="button"
          accessibilityLabel="Thêm ảnh hoặc video"
          disabled={busy || picking}
          onPress={() => void choose()}
          className="flex-row items-center justify-between border border-slate-200 rounded-xl p-4 mt-3"
        >
          <Text className="font-semibold text-slate-700">
            {picking ? "Đang mở thư viện..." : "Thêm ảnh / video"}
          </Text>
          <View className="flex-row gap-3">
            <Icon name="images" color="#16a34a" />
            <Icon name="videocam" color="#ef4444" />
          </View>
        </Pressable>
        <Text className="text-xs text-slate-400 my-3 leading-5">
          {demo
            ? "Bản demo: nội dung và tệp chỉ lưu trong phiên, không tải lên máy chủ."
            : mode === "story"
              ? "Story chưa kết nối máy chủ. Vào bản demo để trải nghiệm."
              : "Ảnh/video chưa kết nối máy chủ; bài văn bản vẫn dùng API thật."}
        </Text>
        {!!error && <Notice message={error} />}
        <Button
          title={
            editing
              ? "Lưu thay đổi"
              : mode === "story"
                ? "Chia sẻ lên tin"
                : "Đăng bài viết"
          }
          busy={busy}
          disabled={!valid || picking || (!demo && mode === "story")}
          onPress={() => void submit()}
        />
      </ScrollView>
    </Sheet>
  );
}
