import { useState } from "react";
import { Image, Pressable, ScrollView, Text, View } from "react-native";
import type { Channel } from "../types";
import { Button, Icon, Notice } from "./ui";
import { EmptyState, Sheet } from "./social-ui";
const covers: Record<string, string> = {
  general:
    "https://images.unsplash.com/photo-1522071820081-009f0129c71c?w=800&auto=format&fit=crop&q=80",
  tech: "https://images.unsplash.com/photo-1558494949-ef010cbdcc31?w=800&auto=format&fit=crop&q=80",
  life: "https://images.unsplash.com/photo-1522202176988-66273c2fd55f?w=800&auto=format&fit=crop&q=80",
};
function ChannelCover({
  channel,
  small = false,
  demo,
}: {
  channel: Channel;
  small?: boolean;
  demo: boolean;
}) {
  const [failed, setFailed] = useState(false);
  const uri = demo ? covers[channel.id] : undefined;
  const dimensions = small
    ? { width: 68, height: 68, borderRadius: 12 }
    : { width: "100%" as const, aspectRatio: 2.8 };
  if (uri && !failed)
    return (
      <Image
        source={{ uri }}
        resizeMode="cover"
        accessibilityLabel={`Ảnh đại diện kênh ${channel.name}`}
        style={dimensions}
        onError={() => setFailed(true)}
      />
    );
  return (
    <View
      style={dimensions}
      className="bg-blue-100 items-center justify-center"
    >
      <Icon name="people" color="#1877f2" size={small ? 30 : 46} />
    </View>
  );
}
export function ChannelsScreen({
  channels,
  joined,
  demo,
  error,
  onRetry,
  onOpen,
  onToggle,
}: {
  channels: Channel[];
  joined: string[];
  demo: boolean;
  error: string;
  onRetry: () => void;
  onOpen: (id: string) => void;
  onToggle: (id: string) => void;
}) {
  const [menu, setMenu] = useState<Channel | null>(null);
  const [leaving, setLeaving] = useState<Channel | null>(null);
  const own = channels.filter((c) => joined.includes(c.id));
  const suggested = demo
    ? channels.filter((c) => !joined.includes(c.id))
    : channels;
  return (
    <View className="flex-1">
      <ScrollView contentContainerStyle={{ paddingBottom: 24 }}>
        <View className="bg-white p-4 border-b border-slate-200">
          <Text className="font-bold text-2xl text-slate-900 mb-5">Kênh</Text>
          <Text className="text-lg font-bold text-slate-900 mb-3">
            Gợi ý cho bạn
          </Text>
          {!!error && <Notice message={error} retry={onRetry} />}
          <ScrollView
            horizontal
            showsHorizontalScrollIndicator={false}
            contentContainerStyle={{ gap: 12 }}
          >
            {suggested.map((channel) => (
              <View
                key={channel.id}
                style={{ width: 240 }}
                className="border border-slate-200 rounded-xl overflow-hidden bg-white"
              >
                <Pressable
                  accessibilityRole="button"
                  accessibilityLabel={`Khám phá kênh ${channel.name}`}
                  onPress={() => onOpen(channel.id)}
                >
                  <ChannelCover channel={channel} demo={demo} />
                </Pressable>
                <View className="p-3">
                  <Text
                    className="font-bold text-slate-900 text-base"
                    numberOfLines={1}
                  >
                    {channel.name}
                  </Text>
                  <Text
                    className="text-xs text-slate-500 mt-1 mb-3 leading-5"
                    numberOfLines={2}
                  >
                    {channel.description ||
                      "Kết nối với đồng nghiệp cùng quan tâm."}
                  </Text>
                  <Button
                    title={
                      demo && channel.membership_policy !== "managed"
                        ? "Tham gia"
                        : "Xem kênh"
                    }
                    onPress={() => {
                      if (demo && channel.membership_policy !== "managed")
                        onToggle(channel.id);
                      else onOpen(channel.id);
                    }}
                  />
                </View>
              </View>
            ))}
          </ScrollView>
          {!suggested.length && (
            <Text className="text-slate-500 text-sm py-4">
              Bạn đã tham gia tất cả kênh hiện có.
            </Text>
          )}
        </View>
        <View className="flex-row items-center justify-between px-4 pt-5 pb-3">
          <Text className="font-bold text-lg text-slate-900">
            Kênh đã tham gia
          </Text>
          {demo && (
            <Text className="text-slate-500 text-sm">{own.length} kênh</Text>
          )}
        </View>
        <View className="bg-white px-4">
          {own.map((channel) => (
            <View key={channel.id} className="flex-row items-center gap-3 py-3">
              <Pressable
                accessibilityRole="button"
                accessibilityLabel={`Mở kênh ${channel.name}`}
                onPress={() => onOpen(channel.id)}
                className="flex-1 flex-row items-center gap-3"
              >
                <ChannelCover channel={channel} small demo={demo} />
                <View className="flex-1">
                  <Text className="font-bold text-base text-slate-900">
                    {channel.name}
                  </Text>
                  <Text className="text-xs text-slate-400 mt-1">
                    Kênh nội bộ · Đã tham gia
                  </Text>
                  <Text
                    className="text-sm text-slate-500 mt-1"
                    numberOfLines={1}
                  >
                    {channel.description}
                  </Text>
                </View>
              </Pressable>
              <Pressable
                accessibilityRole="button"
                accessibilityLabel={`Tùy chọn kênh ${channel.name}`}
                onPress={() => setMenu(channel)}
                className="p-2"
              >
                <Icon name="ellipsis-horizontal" size={22} />
              </Pressable>
            </View>
          ))}
        </View>
        {!own.length && (
          <View className="px-4">
            <EmptyState
              title={
                demo
                  ? "Chưa tham gia kênh nào"
                  : "Chưa kết nối danh sách tham gia"
              }
              detail={
                demo
                  ? "Chọn một kênh gợi ý ở phía trên để tham gia."
                  : "Các kênh truy cập được hiển thị ở phần gợi ý; membership thật chưa được đồng bộ trên mobile."
              }
            />
          </View>
        )}
      </ScrollView>
      {menu && (
        <Sheet title={menu.name} visible onClose={() => setMenu(null)}>
          <View className="p-4 gap-3">
            <Button
              title="Xem bài viết trong kênh"
              onPress={() => {
                onOpen(menu.id);
                setMenu(null);
              }}
            />
            <Button
              title="Rời kênh"
              secondary
              onPress={() => {
                setLeaving(menu);
                setMenu(null);
              }}
            />
          </View>
        </Sheet>
      )}
      {leaving && (
        <Sheet title="Rời kênh?" visible onClose={() => setLeaving(null)}>
          <View className="p-5 gap-4">
            <Text className="text-slate-600 leading-6">
              Bạn sẽ rời kênh {leaving.name}. Bạn có thể tham gia lại từ mục
              Khám phá trong bản demo.
            </Text>
            <Button
              title="Xác nhận rời kênh"
              onPress={() => {
                onToggle(leaving.id);
                setLeaving(null);
              }}
            />
            <Button
              title="Ở lại kênh"
              secondary
              onPress={() => setLeaving(null)}
            />
          </View>
        </Sheet>
      )}
    </View>
  );
}
