import { useEffect, useState } from "react";
import {
  FlatList,
  Pressable,
  ScrollView,
  Text,
  TextInput,
  View,
} from "react-native";
import type { User } from "../types";
import type { SocialTab } from "../hooks/useSocial";
import { Button, Icon } from "./ui";
import { EmptyState, memberFor, Sheet, SocialAvatar } from "./social-ui";
import { members } from "../services/social-demo";
import { matchesSearch } from "../services/search";
import { LibraryScreen } from "../screens/LibraryScreen";
const conversations = [
  {
    id: "luan",
    name: "Võ Thành Luân",
    role: "AI Specialist",
    preview: "Đồ thị tri thức đã đồng bộ thêm 12 nodes chuyên môn mới rồi nhé!",
    messages: [
      {
        me: false,
        text: "Chào Sang! Bạn có tài liệu mô tả kiến trúc Knowledge Graph mới cập nhật không?",
      },
      {
        me: true,
        text: "Tài liệu đã được lưu trong Base Library mục Backend/AI nhé.",
      },
      {
        me: false,
        text: "Đồ thị tri thức đã đồng bộ thêm 12 nodes chuyên môn mới rồi nhé!",
      },
    ],
  },
  {
    id: "phat",
    name: "Phạm Phát",
    role: "Solutions Architect",
    preview: "Đã hoàn tất cấu hình Gateway proxy.",
    messages: [{ me: false, text: "Đã hoàn tất cấu hình Gateway proxy." }],
  },
  {
    id: "phuong",
    name: "Lê Thị Mai Phương",
    role: "HR & Culture",
    preview: "Nhớ gửi danh sách nhân sự tham gia hội thảo tuần này nhé.",
    messages: [
      {
        me: false,
        text: "Nhớ gửi danh sách nhân sự tham gia hội thảo tuần này nhé.",
      },
    ],
  },
];
export function SocialSections({
  tab,
  messageRequest,
  demo,
  user,
  token,
  onNavigate,
  onPost,
}: {
  tab: SocialTab;
  messageRequest?: { name: string; serial: number } | null;
  demo: boolean;
  user: User;
  token: string;
  onNavigate: (tab: SocialTab) => void;
  onPost: (id: string) => void;
}) {
  const [conversation, setConversation] = useState<string | null>(null);
  const [messages, setMessages] = useState<
    Record<string, { me: boolean; text: string }[]>
  >(() => Object.fromEntries(conversations.map((c) => [c.id, c.messages])));
  const [draft, setDraft] = useState("");
  const [opened, setOpened] = useState<string[]>([]);
  const [conversationQuery, setConversationQuery] = useState("");
  useEffect(() => {
    if (!messageRequest) return;
    const person = conversations.find((c) => c.name === messageRequest.name);
    if (person) {
      // eslint-disable-next-line react-hooks/set-state-in-effect -- Open the conversation explicitly requested from global search.
      setConversation(person.id);
      setOpened((items) => [...items, person.id]);
      setDraft("");
    }
  }, [messageRequest]);
  const [read, setRead] = useState<string[]>([]);
  const [section, setSection] = useState<string | null>(null);
  const current = conversations.find((c) => c.id === conversation);
  const notifications = [
    {
      id: "welcome",
      title: "Thông báo từ Toàn công ty",
      body: "Cùng chào đón các thành viên mới gia nhập OpenHello.",
      post: "social-welcome",
      icon: "megaphone-outline" as const,
    },
    {
      id: "tech",
      title: "Cập nhật từ Tech & Architecture",
      body: "Cập nhật mới về pipeline AI Knowledge Graph đang chờ bạn đọc.",
      post: "social-tech",
      icon: "people-outline" as const,
    },
  ];
  if (!["menu", "messages", "notifications"].includes(tab)) return null;
  return (
    <View
      className={`flex-1 ${["messages", "notifications"].includes(tab) ? "bg-white" : ""}`}
    >
      <ScrollView
        keyboardShouldPersistTaps="handled"
        contentContainerStyle={{ padding: 16, paddingBottom: 24 }}
      >
        {tab === "messages" && (
          <>
            <View className="flex-row gap-2 items-center bg-slate-100 rounded-full px-4 mb-3">
              <Icon name="search-outline" size={20} />
              <TextInput
                accessibilityLabel="Tìm kiếm trong Messenger"
                placeholder="Tìm tên người hoặc tin nhắn..."
                value={conversationQuery}
                onChangeText={setConversationQuery}
                autoCapitalize="none"
                autoCorrect={false}
                editable={demo}
                className="flex-1 py-3 text-base text-slate-900 web:outline-none"
              />
              {!!conversationQuery && (
                <Pressable
                  accessibilityRole="button"
                  accessibilityLabel="Xóa tìm kiếm Messenger"
                  onPress={() => setConversationQuery("")}
                  className="p-1"
                >
                  <Icon name="close-circle" size={20} />
                </Pressable>
              )}
            </View>
            {demo ? (
              <>
                {!!conversationQuery &&
                  !conversations.some((c) =>
                    matchesSearch(
                      `${c.name} ${c.role} ${(messages[c.id] || []).map((m) => m.text).join(" ")}`,
                      conversationQuery,
                    ),
                  ) && (
                    <EmptyState
                      title="Không tìm thấy đồng nghiệp"
                      detail="Thử tên khác hoặc tìm không dấu, ví dụ: luan, phat, phuong."
                    />
                  )}
                {conversations
                  .filter((c) =>
                    matchesSearch(
                      `${c.name} ${c.role} ${(messages[c.id] || []).map((m) => m.text).join(" ")}`,
                      conversationQuery,
                    ),
                  )
                  .map((c) => (
                    <Pressable
                      key={c.id}
                      accessibilityRole="button"
                      accessibilityLabel={`Nhắn tin với ${c.name}`}
                      onPress={() => {
                        setConversation(c.id);
                        setOpened((items) => [...items, c.id]);
                      }}
                      className="flex-row items-center gap-3 py-3"
                    >
                      <SocialAvatar
                        size={58}
                        member={{
                          avatar: Object.values(members).find(
                            (member) => member.name === c.name,
                          )?.avatar,
                          name: c.name,
                          initials: c.name
                            .split(" ")
                            .slice(-2)
                            .map((s) => s[0])
                            .join(""),
                          role: c.role,
                          color: "#1877f2",
                        }}
                      />
                      <View className="flex-1">
                        <Text
                          className={`text-base text-slate-900 ${c.id === "luan" && !opened.includes(c.id) ? "font-bold" : "font-semibold"}`}
                        >
                          {c.name}
                        </Text>
                        <View className="flex-row items-center gap-1 mt-1">
                          <Text
                            numberOfLines={1}
                            className={`flex-shrink text-sm ${c.id === "luan" && !opened.includes(c.id) ? "text-slate-900 font-semibold" : "text-slate-500"}`}
                          >
                            {messages[c.id]?.at(-1)?.me ? "Bạn: " : ""}
                            {messages[c.id]?.at(-1)?.text || c.preview}
                          </Text>
                          <Text
                            numberOfLines={1}
                            style={{ flexShrink: 0 }}
                            className="text-xs text-slate-400"
                          >
                            ·{" "}
                            {messages[c.id]?.at(-1)?.me
                              ? "Vừa xong"
                              : c.id === "luan"
                                ? "10:42"
                                : c.id === "phat"
                                  ? "09:15"
                                  : "Hôm qua"}
                          </Text>
                        </View>
                      </View>
                      {c.id === "luan" && !opened.includes(c.id) && (
                        <View
                          accessibilityLabel="Chưa đọc"
                          className="w-3 h-3 bg-blue-600 rounded-full"
                        />
                      )}
                    </Pressable>
                  ))}
              </>
            ) : (
              <EmptyState
                title="Base Message"
                detail="Tích hợp chat thật chưa được triển khai trên mobile. Các chức năng chat demo nằm trong chế độ Khám phá."
              />
            )}
          </>
        )}
        {tab === "notifications" && (
          <>
            <View className="flex-row justify-between items-center mb-5">
              <Text className="text-2xl font-bold text-slate-900">
                Thông báo
              </Text>
              {demo && (
                <Pressable
                  accessibilityRole="button"
                  onPress={() => setRead(notifications.map((n) => n.id))}
                >
                  <Text className="text-blue-600 text-sm">Đọc tất cả</Text>
                </Pressable>
              )}
            </View>
            {demo ? (
              notifications.map((n) => (
                <Pressable
                  key={n.id}
                  accessibilityRole="button"
                  accessibilityLabel={n.title}
                  onPress={() => {
                    setRead((items) =>
                      items.includes(n.id) ? items : [...items, n.id],
                    );
                    onPost(n.post);
                  }}
                  className="flex-row items-center gap-3 py-3"
                >
                  <View className="bg-slate-100 rounded-full p-3 self-start">
                    <Icon name={n.icon} color="#1877f2" size={24} />
                  </View>
                  <View className="flex-1">
                    <Text className="text-sm leading-5 text-slate-700">
                      <Text className="font-semibold text-slate-900">
                        {n.title}.{" "}
                      </Text>
                      {n.body}
                    </Text>
                    <Text className="text-slate-400 text-xs mt-1">
                      Thông báo mẫu
                    </Text>
                  </View>
                  {!read.includes(n.id) && (
                    <View className="w-2 h-2 rounded-full bg-blue-600 mt-2" />
                  )}
                </Pressable>
              ))
            ) : (
              <EmptyState
                title="Thông báo hệ thống"
                detail="Mobile chưa kết nối API thông báo. Bạn có thể xem luồng minh họa trong chế độ demo."
              />
            )}
          </>
        )}
        {tab === "menu" && (
          <>
            <Text className="text-2xl font-bold text-slate-900 mb-4">Menu</Text>
            <Pressable
              accessibilityRole="button"
              accessibilityLabel="Xem trang cá nhân"
              onPress={() => onNavigate("profile")}
              className="flex-row items-center gap-3 p-4 bg-white rounded-xl mb-5"
            >
              <SocialAvatar member={memberFor(user.id, user, demo)} />
              <View className="flex-1">
                <Text className="font-bold text-slate-900 text-base">
                  {user.display_name}
                </Text>
                <Text className="text-slate-500 text-sm mt-1">
                  Xem trang cá nhân
                </Text>
              </View>
              <Icon name="chevron-forward" size={18} />
            </Pressable>
            <Text className="font-bold text-slate-700 mb-3">
              Không gian làm việc
            </Text>
            <View className="flex-row flex-wrap gap-3 mb-6">
              {[
                {
                  title: "Base Inside",
                  detail: "Bảng tin nội bộ",
                  icon: "home-outline" as const,
                  action: () => onNavigate("feed"),
                },
                {
                  title: "Base Message",
                  detail: "Tin nhắn",
                  icon: "chatbubbles-outline" as const,
                  action: () => onNavigate("messages"),
                },
                {
                  title: "Kênh nội bộ",
                  detail: "Cộng đồng & phòng ban",
                  icon: "people-outline" as const,
                  action: () => onNavigate("channels"),
                },
                {
                  title: "Đã lưu",
                  detail: "Bài viết của bạn",
                  icon: "bookmark-outline" as const,
                  action: () => onNavigate("saved"),
                },
                {
                  title: "Base Square",
                  detail: "Kho tri thức",
                  icon: "school-outline" as const,
                  action: () => setSection("Base Square"),
                },
                {
                  title: "Sự kiện",
                  detail: "Lịch hoạt động",
                  icon: "calendar-outline" as const,
                  action: () => setSection("Sự kiện sắp tới"),
                },
              ].map((item) => (
                <Pressable
                  key={item.title}
                  accessibilityRole="button"
                  onPress={item.action}
                  style={{ width: "48%" }}
                  className="bg-white rounded-xl p-4"
                >
                  <Icon name={item.icon} color="#1877f2" size={26} />
                  <Text className="font-semibold text-slate-900 mt-3">
                    {item.title}
                  </Text>
                  <Text className="text-xs text-slate-500 mt-1">
                    {item.detail}
                  </Text>
                </Pressable>
              ))}
            </View>
            <Text className="font-bold text-slate-700 mb-3">Công ty</Text>
            {[
              "Thư từ Ban Giám đốc",
              "Giá trị cốt lõi",
              "Nguyên tắc làm việc",
            ].map((title) => (
              <Pressable
                key={title}
                accessibilityRole="button"
                onPress={() => setSection(title)}
                className="bg-white p-4 flex-row justify-between border-b border-slate-100"
              >
                <Text className="text-slate-700">{title}</Text>
                <Icon name="chevron-forward" size={18} />
              </Pressable>
            ))}
          </>
        )}
      </ScrollView>
      {current && (
        <Sheet
          title={current.name}
          visible
          onClose={() => {
            setConversation(null);
            setDraft("");
          }}
        >
          <View className="bg-blue-50 px-4 py-2">
            <Text className="text-blue-700 text-xs">
              Hội thoại demo · Tin nhắn chưa gửi tới người thật
            </Text>
          </View>
          <FlatList
            data={messages[current.id]}
            keyExtractor={(_, index) => String(index)}
            contentContainerStyle={{ padding: 16 }}
            renderItem={({ item }) => (
              <View
                className={`max-w-[85%] px-4 py-3 rounded-2xl mb-3 ${item.me ? "bg-blue-600 self-end" : "bg-white self-start"}`}
              >
                <Text
                  className={`text-base leading-6 ${item.me ? "text-white" : "text-slate-800"}`}
                >
                  {item.text}
                </Text>
              </View>
            )}
          />
          <View className="flex-row items-end gap-3 p-3 bg-white">
            <TextInput
              accessibilityLabel="Nội dung tin nhắn demo"
              value={draft}
              onChangeText={setDraft}
              maxLength={5000}
              multiline
              placeholder="Nhắn tin..."
              className="flex-1 bg-slate-100 rounded-2xl p-3 max-h-32 text-slate-900"
            />
            <Pressable
              accessibilityRole="button"
              accessibilityLabel="Gửi tin nhắn demo"
              disabled={!draft.trim()}
              onPress={() => {
                setMessages((items) => ({
                  ...items,
                  [current.id]: [
                    ...items[current.id],
                    { me: true, text: draft.trim() },
                  ],
                }));
                setDraft("");
              }}
              className="p-3"
            >
              <Icon name="send" color={draft.trim() ? "#1877f2" : "#94a3b8"} />
            </Pressable>
          </View>
        </Sheet>
      )}
      {!!section && (
        <Sheet title={section} visible onClose={() => setSection(null)}>
          {section === "Base Square" ? (
            <LibraryScreen demo={demo} token={token} />
          ) : (
            <ScrollView contentContainerStyle={{ padding: 20 }}>
              {section === "Sự kiện sắp tới" && demo ? (
                <>
                  <Text className="text-xs text-slate-400 mb-4">
                    Lịch minh họa theo bản thiết kế web
                  </Text>
                  {[
                    {
                      title: "Chia sẻ Tri thức Graph RAG",
                      time: "Thứ 6, 14:00 · Phòng họp 3",
                    },
                    {
                      title: "Demo Base Square v2",
                      time: "Thứ 2 tới · Online Google Meet",
                    },
                  ].map((event) => (
                    <View
                      key={event.title}
                      className="bg-white p-5 rounded-xl border-l-4 border-blue-500 mb-4"
                    >
                      <Text className="font-bold text-slate-900">
                        {event.title}
                      </Text>
                      <Text className="text-slate-500 mt-2">{event.time}</Text>
                    </View>
                  ))}
                </>
              ) : (
                <EmptyState
                  title={section}
                  detail="Mục này được giữ theo cấu trúc web. Nội dung chính thức sẽ hiển thị khi có dữ liệu từ hệ thống."
                />
              )}
              <Button
                title="Quay lại Menu"
                secondary
                onPress={() => setSection(null)}
              />
            </ScrollView>
          )}
        </Sheet>
      )}
    </View>
  );
}
