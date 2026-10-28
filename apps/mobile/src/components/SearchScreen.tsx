import { useState } from "react";
import {
  Modal,
  Pressable,
  ScrollView,
  Text,
  TextInput,
  View,
} from "react-native";
import { SafeAreaView } from "react-native-safe-area-context";
import type { Channel, Member, Post, User } from "../types";
import { members } from "../services/social-demo";
import { matchesSearch } from "../services/search";
import { Button, Icon } from "./ui";
import { EmptyState, memberFor, SocialAvatar } from "./social-ui";
type Filter = "all" | "people" | "posts" | "channels";
export function SearchScreen({
  visible,
  onClose,
  user,
  demo,
  posts,
  channels,
  onPost,
  onChannel,
  onMessage,
  onProfile,
}: {
  visible: boolean;
  onClose: () => void;
  user: User;
  demo: boolean;
  posts: Post[];
  channels: Channel[];
  onPost: (id: string) => void;
  onChannel: (id: string) => void;
  onMessage: (name: string) => void;
  onProfile: () => void;
}) {
  const [query, setQuery] = useState("");
  const [filter, setFilter] = useState<Filter>("all");
  const [history, setHistory] = useState<string[]>([]);
  const [allHistory, setAllHistory] = useState(false);
  const [person, setPerson] = useState<{ id: string; member: Member } | null>(
    null,
  );
  const people = demo
    ? Object.entries(members).map(([id, member]) => ({ id, member }))
    : [{ id: user.id, member: memberFor(user.id, user, false) }];
  const foundPeople = people.filter((p) =>
    matchesSearch(`${p.member.name} ${p.member.role}`, query),
  );
  const foundPosts = posts.filter((p) =>
    matchesSearch(
      `${p.title || ""} ${p.body_plaintext} ${memberFor(p.author_user_id, user, demo).name}`,
      query,
    ),
  );
  const foundChannels = channels.filter((c) =>
    matchesSearch(`${c.name} ${c.description || ""}`, query),
  );
  function remember(value = query) {
    const text = value.trim();
    if (text)
      setHistory((items) =>
        [
          text,
          ...items.filter((item) => item.toLowerCase() !== text.toLowerCase()),
        ].slice(0, 20),
      );
  }
  function closeSearch() {
    setQuery("");
    setFilter("all");
    setPerson(null);
    setAllHistory(false);
    onClose();
  }
  function back() {
    if (person) {
      setPerson(null);
      return;
    }
    if (allHistory) {
      setAllHistory(false);
      return;
    }
    closeSearch();
  }
  const resultsCount =
    (filter === "all" || filter === "people" ? foundPeople.length : 0) +
    (filter === "all" || filter === "posts" ? foundPosts.length : 0) +
    (filter === "all" || filter === "channels" ? foundChannels.length : 0);
  return (
    <Modal
      visible={visible}
      onShow={() => {
        setQuery("");
        setFilter("all");
        setPerson(null);
        setAllHistory(false);
      }}
      animationType="slide"
      presentationStyle="fullScreen"
      onRequestClose={back}
    >
      <SafeAreaView
        edges={["top", "bottom"]}
        style={{ flex: 1, backgroundColor: "white" }}
      >
        <View className="flex-row items-center gap-2 border-b border-slate-100 px-3 py-2">
          <Pressable
            accessibilityRole="button"
            accessibilityLabel="Quay lại từ tìm kiếm"
            onPress={back}
            className="p-2"
          >
            <Icon name="arrow-back" color="#0f172a" size={25} />
          </Pressable>
          <View className="flex-1 flex-row items-center rounded-full bg-slate-100 px-3">
            <Icon name="search-outline" size={19} />
            <TextInput
              accessibilityLabel="Tìm kiếm OpenHello"
              placeholder="Tìm kiếm trên OpenHello"
              value={query}
              onChangeText={(text) => {
                setQuery(text);
                setPerson(null);
                setAllHistory(false);
              }}
              autoFocus
              returnKeyType="search"
              onSubmitEditing={() => remember()}
              autoCapitalize="none"
              autoCorrect={false}
              className="flex-1 py-3 px-2 text-base text-slate-900 web:outline-none"
            />
            {!!query && (
              <Pressable
                accessibilityRole="button"
                accessibilityLabel="Xóa từ khóa tìm kiếm"
                onPress={() => {
                  setQuery("");
                  setPerson(null);
                }}
                className="p-1"
              >
                <Icon name="close-circle" size={20} />
              </Pressable>
            )}
          </View>
        </View>
        <ScrollView
          keyboardShouldPersistTaps="handled"
          contentContainerStyle={{ padding: 16, paddingBottom: 30 }}
        >
          {person ? (
            <View className="items-center pt-8">
              <SocialAvatar member={person.member} large />
              <Text className="text-2xl text-slate-900 font-bold text-center mt-4">
                {person.member.name}
              </Text>
              <Text className="text-slate-500 text-center mt-2 mb-6">
                {person.member.role}
              </Text>
              {person.id === user.id ? (
                <Button
                  title="Xem trang cá nhân của tôi"
                  onPress={() => {
                    closeSearch();
                    onProfile();
                  }}
                />
              ) : demo ? (
                <Button
                  title="Nhắn tin"
                  onPress={() => {
                    remember(person.member.name);
                    setPerson(null);
                    closeSearch();
                    onMessage(person.member.name);
                  }}
                />
              ) : (
                <Text className="text-slate-400">
                  Danh bạ chưa được kết nối.
                </Text>
              )}
            </View>
          ) : !query.trim() ? (
            <>
              <View className="flex-row items-center justify-between mb-3">
                <Text className="text-lg font-bold text-slate-900">
                  Tìm kiếm gần đây
                </Text>
                {history.length > 0 && (
                  <Pressable
                    accessibilityRole="button"
                    onPress={() => setAllHistory((v) => !v)}
                    className="py-2"
                  >
                    <Text className="text-blue-600">
                      {allHistory ? "Thu gọn" : "Xem tất cả"}
                    </Text>
                  </Pressable>
                )}
              </View>
              {!history.length && (
                <Text className="text-slate-400 text-sm mb-5">
                  Các tìm kiếm của bạn sẽ xuất hiện tại đây.
                </Text>
              )}
              {(allHistory ? history : history.slice(0, 5)).map((term) => (
                <View key={term} className="flex-row items-center mb-1">
                  <Pressable
                    accessibilityRole="button"
                    accessibilityLabel={`Tìm lại ${term}`}
                    onPress={() => {
                      setQuery(term);
                      setAllHistory(false);
                    }}
                    className="flex-row flex-1 items-center gap-3 py-3"
                  >
                    <View className="rounded-full bg-slate-100 p-2">
                      <Icon name="time-outline" size={21} />
                    </View>
                    <Text
                      className="text-slate-800 text-base flex-1"
                      numberOfLines={1}
                    >
                      {term}
                    </Text>
                  </Pressable>
                  <Pressable
                    accessibilityRole="button"
                    accessibilityLabel={`Xóa lịch sử ${term}`}
                    onPress={() =>
                      setHistory((items) =>
                        items.filter((item) => item !== term),
                      )
                    }
                    className="p-3"
                  >
                    <Icon name="close" size={21} />
                  </Pressable>
                </View>
              ))}
              {allHistory && history.length > 0 && (
                <Pressable
                  accessibilityRole="button"
                  onPress={() => setHistory([])}
                  className="py-3"
                >
                  <Text className="text-blue-600 text-center">
                    Xóa tất cả lịch sử
                  </Text>
                </Pressable>
              )}
              <Text className="text-lg font-bold text-slate-900 mt-5 mb-4">
                Đồng nghiệp bạn có thể biết
              </Text>
              <ScrollView
                horizontal
                showsHorizontalScrollIndicator={false}
                contentContainerStyle={{ gap: 12 }}
              >
                {people
                  .filter((p) => p.id !== user.id)
                  .map((p) => (
                    <Pressable
                      accessibilityRole="button"
                      accessibilityLabel={`Xem đồng nghiệp ${p.member.name}`}
                      key={p.id}
                      onPress={() => {
                        remember(p.member.name);
                        setPerson(p);
                      }}
                      className="rounded-xl border border-slate-200 p-4 items-center w-40"
                    >
                      <SocialAvatar member={p.member} large />
                      <Text className="font-semibold text-slate-900 text-center mt-3">
                        {p.member.name}
                      </Text>
                      <Text
                        numberOfLines={2}
                        className="text-xs text-slate-500 text-center mt-2"
                      >
                        {p.member.role}
                      </Text>
                    </Pressable>
                  ))}
              </ScrollView>
              {!demo && (
                <Text className="text-slate-400 mt-3">
                  Danh bạ người dùng chưa được kết nối; tìm kiếm hiện giới hạn
                  dữ liệu đã tải.
                </Text>
              )}
              <Text className="text-lg font-bold text-slate-900 mt-6 mb-3">
                Khám phá các kênh
              </Text>
              {channels.map((c) => (
                <Pressable
                  accessibilityRole="button"
                  key={c.id}
                  onPress={() => {
                    remember(c.name);
                    closeSearch();
                    onChannel(c.id);
                  }}
                  className="flex-row gap-3 items-center py-3"
                >
                  <View className="bg-blue-50 p-3 rounded-xl">
                    <Icon name="people-outline" color="#1877f2" />
                  </View>
                  <Text className="font-medium text-slate-800 flex-1">
                    {c.name}
                  </Text>
                  <Icon name="chevron-forward" size={17} />
                </Pressable>
              ))}
            </>
          ) : (
            <>
              <ScrollView
                horizontal
                showsHorizontalScrollIndicator={false}
                contentContainerStyle={{ gap: 8, paddingBottom: 16 }}
              >
                {(
                  [
                    { id: "all", label: "Tất cả" },
                    { id: "people", label: "Mọi người" },
                    { id: "posts", label: "Bài viết" },
                    { id: "channels", label: "Kênh" },
                  ] as const
                ).map((item) => (
                  <Pressable
                    accessibilityRole="button"
                    accessibilityState={{ selected: filter === item.id }}
                    key={item.id}
                    onPress={() => setFilter(item.id)}
                    className={`rounded-full px-4 py-2 ${filter === item.id ? "bg-blue-600" : "bg-slate-100"}`}
                  >
                    <Text
                      className={
                        filter === item.id
                          ? "text-white font-semibold"
                          : "text-slate-600"
                      }
                    >
                      {item.label}
                    </Text>
                  </Pressable>
                ))}
              </ScrollView>
              <Text className="text-xs text-slate-400 mb-3">
                {demo ? "Tìm trong dữ liệu demo" : "Tìm trong dữ liệu đã tải"} ·{" "}
                {resultsCount} kết quả
              </Text>
              {(filter === "all" || filter === "people") &&
                foundPeople.length > 0 && (
                  <>
                    <Text className="font-bold text-lg text-slate-900 my-3">
                      Mọi người
                    </Text>
                    {foundPeople.map((p) => (
                      <Pressable
                        accessibilityRole="button"
                        accessibilityLabel={`Xem hồ sơ ${p.member.name}`}
                        key={p.id}
                        onPress={() => {
                          remember();
                          setPerson(p);
                        }}
                        className="flex-row items-center gap-3 py-3"
                      >
                        <SocialAvatar member={p.member} />
                        <View className="flex-1">
                          <Text className="font-semibold text-slate-900">
                            {p.member.name}
                          </Text>
                          <Text className="text-xs text-slate-500 mt-1">
                            {p.member.role}
                          </Text>
                        </View>
                        <Icon name="chevron-forward" size={18} />
                      </Pressable>
                    ))}
                  </>
                )}
              {(filter === "all" || filter === "posts") &&
                foundPosts.length > 0 && (
                  <>
                    <Text className="font-bold text-lg text-slate-900 my-3">
                      Bài viết
                    </Text>
                    {foundPosts.map((post) => (
                      <Pressable
                        accessibilityRole="button"
                        accessibilityLabel={`Mở bài viết ${post.title || post.body_plaintext.slice(0, 35)}`}
                        key={post.id}
                        onPress={() => {
                          remember();
                          closeSearch();
                          onPost(post.id);
                        }}
                        className="border-b border-slate-100 py-4"
                      >
                        <Text className="font-semibold text-slate-900">
                          {post.title ||
                            memberFor(post.author_user_id, user, demo).name}
                        </Text>
                        <Text
                          numberOfLines={3}
                          className="text-slate-600 leading-6 mt-2"
                        >
                          {post.body_plaintext}
                        </Text>
                      </Pressable>
                    ))}
                  </>
                )}
              {(filter === "all" || filter === "channels") &&
                foundChannels.length > 0 && (
                  <>
                    <Text className="font-bold text-lg text-slate-900 my-3">
                      Kênh nội bộ
                    </Text>
                    {foundChannels.map((c) => (
                      <Pressable
                        accessibilityRole="button"
                        key={c.id}
                        onPress={() => {
                          remember();
                          closeSearch();
                          onChannel(c.id);
                        }}
                        className="flex-row gap-3 items-center py-3"
                      >
                        <Icon name="people-outline" color="#1877f2" />
                        <View className="flex-1">
                          <Text className="font-semibold text-slate-900">
                            {c.name}
                          </Text>
                          <Text className="text-slate-500 text-xs mt-1">
                            {c.description}
                          </Text>
                        </View>
                        <Icon name="chevron-forward" size={18} />
                      </Pressable>
                    ))}
                  </>
                )}
              {resultsCount === 0 && (
                <EmptyState
                  title="Không tìm thấy kết quả"
                  detail="Thử tên đồng nghiệp, nội dung bài viết hoặc tên kênh. Bạn có thể tìm không dấu."
                />
              )}
            </>
          )}
        </ScrollView>
      </SafeAreaView>
    </Modal>
  );
}
