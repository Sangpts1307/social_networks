import { useState } from "react";
import {
  ActivityIndicator,
  FlatList,
  Pressable,
  ScrollView,
  Text,
  View,
} from "react-native";
import type { ComposerMode, Post, User } from "../types";
import { useSocial, type SocialTab } from "../hooks/useSocial";
import { Button, Icon, Notice } from "../components/ui";
import {
  EmptyState,
  memberFor,
  Sheet,
  SocialAvatar,
} from "../components/social-ui";
import { PostCard } from "../components/PostCard";
import { StoriesRail, StoryViewer } from "../components/Stories";
import { PostComposer } from "../components/PostComposer";
import { ChannelsScreen } from "../components/ChannelsScreen";
import { SearchScreen } from "../components/SearchScreen";
import { SocialSections } from "../components/SocialSections";
import { CommentsSheet } from "../components/CommentsSheet";
export function FeedScreen({
  token,
  user,
  demo,
  onLogout,
  loggingOut,
  logoutError,
}: {
  token: string;
  user: User;
  demo: boolean;
  onLogout: () => void;
  loggingOut: boolean;
  logoutError: string;
}) {
  const social = useSocial(token, user, demo);
  const [showSearch, setShowSearch] = useState(false);
  const [messageRequest, setMessageRequest] = useState<{
    name: string;
    serial: number;
  } | null>(null);
  const [composer, setComposer] = useState<{
    post?: Post;
    mode?: ComposerMode;
  } | null>(null);
  const [storyId, setStoryId] = useState<string | null>(null);
  const activeStory = social.stories.find((story) => story.id === storyId);
  const [detailId, setDetailId] = useState<string | null>(null);
  const [menu, setMenu] = useState<Post | null>(null);
  const [deletePost, setDeletePost] = useState<Post | null>(null);
  const detail = social.allPosts.find((p) => p.id === detailId);
  const me = memberFor(user.id, user, demo);
  const tabs: {
    id: SocialTab;
    label: string;
    activeIcon:
      "home" | "people" | "chatbubble-ellipses" | "notifications" | "menu";
    icon:
      | "home-outline"
      | "people-outline"
      | "chatbubble-ellipses-outline"
      | "notifications-outline"
      | "menu-outline";
  }[] = [
    { id: "feed", label: "Bảng tin", icon: "home-outline", activeIcon: "home" },
    {
      id: "channels",
      label: "Kênh",
      icon: "people-outline",
      activeIcon: "people",
    },
    {
      id: "messages",
      label: "Tin nhắn",
      icon: "chatbubble-ellipses-outline",
      activeIcon: "chatbubble-ellipses",
    },
    {
      id: "notifications",
      label: "Thông báo",
      icon: "notifications-outline",
      activeIcon: "notifications",
    },
    { id: "menu", label: "Menu", icon: "menu-outline", activeIcon: "menu" },
  ];
  const catchAction = (action: Promise<unknown>) => {
    void action.catch(() => {});
  };
  const selectedChannel = social.channels.find((c) => c.id === social.channel);
  const heading =
    social.tab === "saved"
      ? "Bài viết đã lưu"
      : social.tab === "profile"
        ? "Bài viết của tôi"
        : selectedChannel?.name || "Bảng tin nội bộ";
  const feedHeader = (
    <View>
      {social.tab === "profile" && (
        <View className="bg-white rounded-xl border border-slate-100 overflow-hidden mb-5">
          <View className="bg-blue-600 h-20">
            <View className="absolute right-5 top-3 rounded-full border border-blue-400 w-20 h-20" />
            <View className="absolute right-20 -top-6 rounded-full border border-blue-400 w-28 h-28" />
          </View>
          <View className="px-5 pb-5">
            <View className="-mt-8 self-start border-4 border-white rounded-xl">
              <SocialAvatar member={me} large />
            </View>
            <Text className="font-bold text-xl text-slate-900 mt-3">
              {user.display_name}
            </Text>
            <Text className="text-sm text-slate-500 mt-1">{me.role}</Text>
            <Text className="text-slate-400 text-xs mt-2">{user.email}</Text>
            <View className="bg-slate-50 rounded-2xl p-4 my-4">
              <Text className="text-slate-600 leading-6">
                {demo
                  ? "Luôn tò mò, thích học hỏi và tin rằng những kết nối tốt tạo nên sản phẩm tốt. ✨"
                  : "Không gian cá nhân để xem lại những chia sẻ của bạn cùng đồng đội."}
              </Text>
            </View>
            {!!logoutError && <Notice message={logoutError} />}
            <Button
              title={demo ? "Thoát bản demo" : "Đăng xuất"}
              secondary
              busy={loggingOut}
              onPress={onLogout}
            />
          </View>
        </View>
      )}
      {social.tab === "saved" && (
        <View className="mb-5">
          <Text className="text-2xl font-bold text-slate-900">Góc lưu giữ</Text>
          <Text className="text-slate-500 leading-6 mt-2">
            Những chia sẻ bạn muốn đọc lại, luôn ở đây.
          </Text>
        </View>
      )}
      {social.tab === "feed" && (
        <View className="bg-white px-3 py-3 mb-2 border-b border-slate-200">
          <View className="flex-row items-center gap-3">
            <Pressable
              accessibilityRole="button"
              accessibilityLabel="Mở hồ sơ từ bảng tin"
              onPress={() => social.navigate("profile")}
            >
              <SocialAvatar member={me} />
            </Pressable>
            <Pressable
              accessibilityRole="button"
              accessibilityLabel="Tạo bài viết mới"
              onPress={() => setComposer({})}
              className="flex-1 rounded-full border border-slate-300 px-4 py-3"
            >
              <Text className="text-slate-600">
                Bạn đang nghĩ gì, {user.display_name}?
              </Text>
            </Pressable>
          </View>
          <View className="flex-row justify-around border-t border-slate-100 mt-3 pt-3">
            <Pressable
              accessibilityRole="button"
              onPress={() => setComposer({})}
              className="flex-row gap-2 items-center"
            >
              <Icon name="create-outline" color="#16a34a" size={20} />
              <Text className="text-sm font-semibold text-slate-600">
                Ảnh / Video
              </Text>
            </Pressable>
            <Pressable
              accessibilityRole="button"
              onPress={() => social.navigate("channels")}
              className="flex-row gap-2 items-center"
            >
              <Icon name="people" color="#1877f2" size={20} />
              <Text className="text-sm font-semibold text-slate-600">
                Kênh nội bộ
              </Text>
            </Pressable>
          </View>
        </View>
      )}
      {social.tab === "feed" && (
        <StoriesRail
          stories={social.stories}
          user={user}
          demo={demo}
          onCreate={() => setComposer({ mode: "story" })}
          onOpen={setStoryId}
        />
      )}
      <View className="flex-row items-center justify-between mb-3">
        <Text className="font-bold text-slate-900 text-lg">{heading}</Text>
        {!!social.channel && (
          <Pressable
            accessibilityRole="button"
            onPress={() => social.setChannel("")}
            className="p-2"
          >
            <Text className="text-xs text-blue-600">Tất cả kênh</Text>
          </Pressable>
        )}
      </View>
      {social.tab === "feed" && (
        <ScrollView
          horizontal
          showsHorizontalScrollIndicator={false}
          contentContainerStyle={{ gap: 8, paddingBottom: 16 }}
        >
          <Pressable
            accessibilityRole="button"
            accessibilityState={{ selected: !social.channel }}
            onPress={() => social.setChannel("")}
            className={`rounded-full px-4 py-2.5 ${!social.channel ? "bg-blue-600" : "bg-white border border-slate-200"}`}
          >
            <Text
              className={`text-[10px] font-semibold ${!social.channel ? "text-white" : "text-slate-500"}`}
            >
              Tất cả
            </Text>
          </Pressable>
          {social.channels.map((c) => (
            <Pressable
              key={c.id}
              accessibilityRole="button"
              accessibilityState={{ selected: social.channel === c.id }}
              onPress={() => social.setChannel(c.id)}
              className={`rounded-full px-4 py-2.5 ${social.channel === c.id ? "bg-blue-600" : "bg-white border border-slate-200"}`}
            >
              <Text
                className={`text-[10px] font-semibold ${social.channel === c.id ? "text-white" : "text-slate-500"}`}
              >
                # {c.name}
              </Text>
            </Pressable>
          ))}
        </ScrollView>
      )}
      {!!social.error && (
        <Notice message={social.error} retry={() => void social.load()} />
      )}
    </View>
  );
  return (
    <View className="flex-1 w-full max-w-3xl self-center">
      <View className="bg-white px-4 py-3 flex-row justify-between items-center border-b border-slate-200">
        <Pressable
          accessibilityRole="button"
          accessibilityLabel="Về bảng tin"
          onPress={() => social.navigate("feed")}
        >
          <Text
            style={{
              color: "#1877f2",
              fontSize: 27,
              fontWeight: "800",
              letterSpacing: -1,
            }}
          >
            OpenHello
          </Text>
          <Text className="text-[10px] text-slate-400">
            BASE INSIDE {demo ? "· DEMO" : ""}
          </Text>
        </Pressable>
        <View className="flex-row items-center gap-2">
          <Pressable
            accessibilityRole="button"
            accessibilityLabel="Mở tìm kiếm OpenHello"
            onPress={() => {
              setShowSearch(true);
            }}
            className="rounded-full bg-slate-100 p-2.5"
          >
            <Icon name="search" color="#0f172a" size={22} />
          </Pressable>
          <Pressable
            accessibilityRole="button"
            accessibilityLabel="Mở Base Message"
            onPress={() => social.navigate("messages")}
            className="rounded-full bg-slate-100 p-2.5"
          >
            <Icon name="chatbubble-ellipses" color="#0f172a" size={22} />
          </Pressable>
        </View>
      </View>
      <SearchScreen
        visible={showSearch}
        onClose={() => setShowSearch(false)}
        user={user}
        demo={demo}
        posts={social.allPosts}
        channels={social.channels}
        onPost={(id) => setDetailId(id)}
        onChannel={social.openChannel}
        onProfile={() => social.navigate("profile")}
        onMessage={(name) => {
          setMessageRequest({ name, serial: Date.now() });
          social.navigate("messages");
        }}
      />
      <SocialSections
        messageRequest={messageRequest}
        tab={social.tab}
        demo={demo}
        user={user}
        token={token}
        onNavigate={social.navigate}
        onPost={(id) => {
          social.navigate("feed");
          setDetailId(id);
        }}
      />
      {social.tab === "channels" ? (
        <ChannelsScreen
          channels={social.channels}
          joined={social.joined}
          demo={demo}
          error={social.error}
          onRetry={() => void social.load()}
          onOpen={social.openChannel}
          onToggle={social.toggleJoin}
        />
      ) : ["menu", "messages", "notifications"].includes(social.tab) ? null : (
        <FlatList
          data={social.posts}
          keyExtractor={(p) => p.id}
          contentContainerStyle={{
            padding: 0,
            paddingTop: 8,
            paddingBottom: 12,
          }}
          ListHeaderComponent={<View className="px-2">{feedHeader}</View>}
          refreshing={social.loading && social.posts.length > 0}
          onRefresh={() => void social.load()}
          renderItem={({ item }) => (
            <PostCard
              post={item}
              user={user}
              demo={demo}
              channelName={
                social.channels.find((c) => c.id === item.channel_id)?.name ||
                "Nội bộ"
              }
              busy={social.busy.some((key) => key.endsWith(`:${item.id}`))}
              onReact={(reaction) => catchAction(social.react(item, reaction))}
              onSave={() => catchAction(social.save(item))}
              onComments={() => setDetailId(item.id)}
              onMenu={() => setMenu(item)}
            />
          )}
          ListEmptyComponent={
            social.loading ? (
              <View className="py-10">
                <ActivityIndicator color="#1877f2" />
                <Text className="text-center text-slate-400 mt-3">
                  Đang tải bài viết…
                </Text>
              </View>
            ) : (
              <EmptyState
                title={
                  social.search
                    ? "Chưa tìm thấy bài viết"
                    : social.tab === "saved"
                      ? "Chưa có bài viết đã lưu"
                      : social.tab === "profile"
                        ? "Câu chuyện của bạn bắt đầu ở đây"
                        : "Kênh đang chờ câu chuyện đầu tiên"
                }
                detail={
                  social.tab === "saved"
                    ? "Nhấn Lưu ở một bài viết để thêm vào góc riêng của bạn."
                    : "Thử một từ khóa khác hoặc chia sẻ điều bạn đang nghĩ."
                }
                action={
                  social.tab !== "saved" ? (
                    <Button
                      title="Chia sẻ bài viết"
                      onPress={() => setComposer({})}
                    />
                  ) : undefined
                }
              />
            )
          }
          ListFooterComponent={
            social.cursor ? (
              <View className="pb-4">
                <Button
                  title="Tải thêm bài viết"
                  secondary
                  busy={social.loading}
                  onPress={() => void social.load(social.cursor || undefined)}
                />
              </View>
            ) : undefined
          }
        />
      )}
      {!!social.toast && (
        <View
          pointerEvents="none"
          className="absolute bottom-24 left-5 right-5 bg-slate-900 rounded-2xl px-4 py-3"
        >
          <Text
            accessibilityRole="alert"
            className="text-white text-sm text-center"
          >
            {social.toast}
          </Text>
        </View>
      )}
      <View
        accessibilityRole="tablist"
        className="bg-white border-t border-slate-100 flex-row px-2 py-2"
      >
        {tabs.map((t) => {
          const active =
            social.tab === t.id ||
            (t.id === "menu" && ["saved", "profile"].includes(social.tab));
          return (
            <Pressable
              key={t.id}
              accessibilityRole="tab"
              accessibilityLabel={t.label}
              accessibilityState={{ selected: active }}
              onPress={() => social.navigate(t.id)}
              className="flex-1 items-center justify-center gap-1 py-2"
            >
              <Icon
                name={active ? t.activeIcon : t.icon}
                color={active ? "#1877f2" : "#94a3b8"}
                size={24}
              />
              <Text
                className={`text-[10px] ${active ? "font-bold text-blue-600" : "font-medium text-slate-400"}`}
              >
                {t.label}
              </Text>
            </Pressable>
          );
        })}
      </View>
      {composer && (
        <PostComposer
          key={composer.post?.id || "new"}
          user={user}
          demo={demo}
          channels={
            demo && !composer.post
              ? social.channels.filter((c) => social.joined.includes(c.id))
              : social.channels
          }
          initialChannel={social.channel || undefined}
          editing={composer.post}
          onClose={() => setComposer(null)}
          initialMode={composer.mode}
          onStory={social.publishStory}
          onPublish={social.publish}
        />
      )}
      {activeStory && (
        <StoryViewer
          story={activeStory}
          stories={social.stories}
          user={user}
          demo={demo}
          onClose={() => setStoryId(null)}
          onSelect={setStoryId}
        />
      )}
      {detail && (
        <CommentsSheet
          key={detail.id}
          post={detail}
          user={user}
          demo={demo}
          token={token}
          initial={social.comments[detail.id] || []}
          onClose={() => setDetailId(null)}
          onAdd={social.addComment}
        />
      )}
      {menu && (
        <Sheet title="Tùy chọn bài viết" visible onClose={() => setMenu(null)}>
          <View className="p-5 gap-3">
            <Button
              title={menu.is_saved ? "Bỏ lưu bài viết" : "Lưu bài viết"}
              secondary
              onPress={() => {
                catchAction(social.save(menu));
                setMenu(null);
              }}
            />
            {menu.author_user_id === user.id && (
              <>
                <Button
                  title="Chỉnh sửa bài viết"
                  secondary
                  onPress={() => {
                    setComposer({ post: menu });
                    setMenu(null);
                  }}
                />
                <Button
                  title="Xóa bài viết"
                  secondary
                  onPress={() => {
                    setDeletePost(menu);
                    setMenu(null);
                  }}
                />
              </>
            )}
            <Button title="Đóng" secondary onPress={() => setMenu(null)} />
          </View>
        </Sheet>
      )}
      {deletePost && (
        <Sheet
          title="Xóa bài viết?"
          visible
          disabled={social.busy.includes(`delete:${deletePost.id}`)}
          onClose={() => setDeletePost(null)}
        >
          <View className="p-5 gap-4">
            <Text className="text-slate-600 leading-6">
              Bài viết sẽ được gỡ khỏi bảng tin. Bạn muốn tiếp tục?
            </Text>
            {!!social.error && <Notice message={social.error} />}
            <Button
              title="Xác nhận xóa"
              busy={social.busy.includes(`delete:${deletePost.id}`)}
              onPress={() => {
                void social
                  .remove(deletePost)
                  .then(() => setDeletePost(null))
                  .catch(() => {});
              }}
            />
            <Button
              title="Giữ lại bài viết"
              secondary
              disabled={social.busy.includes(`delete:${deletePost.id}`)}
              onPress={() => setDeletePost(null)}
            />
          </View>
        </Sheet>
      )}
    </View>
  );
}
