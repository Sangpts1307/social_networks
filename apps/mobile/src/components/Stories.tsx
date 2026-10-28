import { Image, Modal, Pressable, ScrollView, Text, View } from "react-native";
import { SafeAreaView } from "react-native-safe-area-context";
import type { Story, User } from "../types";
import { Icon } from "./ui";
import { memberFor, relativeTime, SocialAvatar } from "./social-ui";
import { MediaPreview } from "./MediaPreview";
export function StoriesRail({
  stories,
  user,
  demo,
  onCreate,
  onOpen,
}: {
  stories: Story[];
  user: User;
  demo: boolean;
  onCreate: () => void;
  onOpen: (id: string) => void;
}) {
  return (
    <ScrollView
      horizontal
      showsHorizontalScrollIndicator={false}
      contentContainerStyle={{
        gap: 10,
        paddingVertical: 12,
        paddingHorizontal: 4,
      }}
    >
      <Pressable
        accessibilityRole="button"
        accessibilityLabel="Tạo Story mới"
        onPress={onCreate}
        style={{ width: 106, height: 164 }}
        className="bg-white rounded-xl overflow-hidden border border-slate-200"
      >
        <View className="flex-1 bg-blue-50 items-center justify-center">
          <SocialAvatar member={memberFor(user.id, user, demo)} large />
        </View>
        <View className="items-center pb-3">
          <View className="bg-blue-600 rounded-full p-1 border-4 border-white -mt-4">
            <Icon name="add" color="white" size={21} />
          </View>
          <Text className="font-semibold text-xs text-slate-900 mt-1">
            Tạo tin
          </Text>
        </View>
      </Pressable>
      {stories.map((story) => {
        const member = memberFor(story.author_user_id, user, demo);
        return (
          <Pressable
            key={story.id}
            accessibilityRole="button"
            accessibilityLabel={`Xem Story của ${member.name}`}
            onPress={() => onOpen(story.id)}
            style={{
              width: 106,
              height: 164,
              backgroundColor: story.background,
            }}
            className="rounded-xl overflow-hidden"
          >
            <View className="absolute inset-0">
              {story.media[0]?.type === "image" && (
                <Image
                  source={{ uri: story.media[0].uri }}
                  resizeMode="cover"
                  style={{ width: "100%", height: "100%" }}
                />
              )}
            </View>
            <View className="absolute inset-0 bg-black/20" />
            <View className="p-2">
              <View className="self-start rounded-full border-2 border-blue-500">
                <SocialAvatar member={member} />
              </View>
            </View>
            {story.media[0]?.type === "video" ? (
              <View className="items-center mt-1">
                <Icon name="play-circle" color="white" size={32} />
              </View>
            ) : (
              !story.media.length && (
                <Text
                  className="text-white text-xs px-2 text-center font-semibold"
                  numberOfLines={3}
                >
                  {story.body}
                </Text>
              )
            )}
            <Text
              numberOfLines={2}
              className="absolute bottom-2 left-2 right-2 text-white text-xs font-bold"
            >
              {story.author_user_id === user.id ? "Tin của bạn" : member.name}
            </Text>
          </Pressable>
        );
      })}
    </ScrollView>
  );
}
export function StoryViewer({
  story,
  stories,
  user,
  demo,
  onClose,
  onSelect,
}: {
  story: Story;
  stories: Story[];
  user: User;
  demo: boolean;
  onClose: () => void;
  onSelect: (id: string) => void;
}) {
  const index = stories.findIndex((s) => s.id === story.id);
  const member = memberFor(story.author_user_id, user, demo);
  return (
    <Modal
      visible
      animationType="fade"
      presentationStyle="fullScreen"
      onRequestClose={onClose}
    >
      <SafeAreaView style={{ flex: 1, backgroundColor: "#020617" }}>
        <View className="flex-row gap-1 p-3">
          {stories.map((s, i) => (
            <View
              key={s.id}
              className={`h-1 flex-1 rounded-full ${i <= index ? "bg-white" : "bg-white/30"}`}
            />
          ))}
        </View>
        <View className="flex-row items-center gap-3 px-4 pb-3">
          <SocialAvatar member={member} />
          <View className="flex-1">
            <Text className="text-white font-bold">{member.name}</Text>
            <Text className="text-slate-300 text-xs mt-1">
              {relativeTime(story.created_at)} · Tin 24 giờ · Demo
            </Text>
          </View>
          <Pressable
            accessibilityRole="button"
            accessibilityLabel="Đóng Story"
            onPress={onClose}
            className="p-2"
          >
            <Icon name="close" color="white" size={26} />
          </Pressable>
        </View>
        <View
          style={{
            flex: 1,
            backgroundColor: story.background,
            justifyContent: "center",
          }}
        >
          {story.media[0] && (
            <MediaPreview key={story.media[0].id} media={story.media[0]} tall />
          )}
          {!!story.body && (
            <Text
              className={`text-white text-center px-6 py-6 font-semibold ${story.media.length ? "text-lg" : "text-3xl"}`}
            >
              {story.body}
            </Text>
          )}
        </View>
        <View className="flex-row justify-between items-center p-4">
          <Pressable
            accessibilityRole="button"
            accessibilityLabel="Story trước"
            disabled={index <= 0}
            onPress={() => onSelect(stories[index - 1].id)}
            className={`p-3 ${index <= 0 ? "opacity-30" : ""}`}
          >
            <Icon name="chevron-back" color="white" size={24} />
          </Pressable>
          <Text className="text-slate-300 text-xs">
            {index + 1} / {stories.length}
          </Text>
          <Pressable
            accessibilityRole="button"
            accessibilityLabel={
              index < stories.length - 1
                ? "Story tiếp theo"
                : "Kết thúc xem Story"
            }
            onPress={() =>
              index < stories.length - 1
                ? onSelect(stories[index + 1].id)
                : onClose()
            }
            className="p-3"
          >
            <Icon name="chevron-forward" color="white" size={24} />
          </Pressable>
        </View>
      </SafeAreaView>
    </Modal>
  );
}
