import { useState, type ReactNode } from "react";
import {
  Image,
  Modal,
  Pressable,
  Text,
  View,
  KeyboardAvoidingView,
  Platform,
} from "react-native";
import { SafeAreaView } from "react-native-safe-area-context";
import { Icon } from "./ui";
import type { Member, User } from "../types";
import { members } from "../services/social-demo";
export function memberFor(id: string, user: User, demo: boolean): Member {
  if (id === user.id)
    return {
      avatar: demo ? members["demo-user"].avatar : undefined,
      name: user.display_name,
      role: demo ? "Lead Developer · Ban Công nghệ" : "Thành viên",
      initials: user.display_name
        .split(" ")
        .slice(-2)
        .map((s) => s[0])
        .join(""),
      color: "#1877f2",
    };
  if (demo && members[id]) return members[id];
  return {
    name: `Thành viên · ${id.slice(0, 8)}`,
    role: "Đồng nghiệp",
    initials: "TV",
    color: "#64748b",
  };
}
export function SocialAvatar({
  member,
  large = false,
  size,
}: {
  member: Member;
  large?: boolean;
  size?: number;
}) {
  const [failed, setFailed] = useState(false);
  const avatar = member.avatar;
  if (avatar && !failed)
    return (
      <Image
        accessibilityLabel={member.name}
        source={{ uri: avatar }}
        onError={() => setFailed(true)}
        style={{
          width: size ?? (large ? 80 : 42),
          height: size ?? (large ? 80 : 42),
          borderRadius: 999,
        }}
      />
    );
  return (
    <View
      style={{
        backgroundColor: `${member.color}15`,
        width: size ?? (large ? 80 : 42),
        height: size ?? (large ? 80 : 42),
        borderRadius: 999,
        alignItems: "center",
        justifyContent: "center",
      }}
    >
      <Text
        style={{
          color: member.color,
          fontSize: large ? 24 : 14,
          fontWeight: "700",
        }}
      >
        {member.initials}
      </Text>
    </View>
  );
}
export function EmptyState({
  title,
  detail,
  action,
}: {
  title: string;
  detail: string;
  action?: ReactNode;
}) {
  return (
    <View className="items-center bg-white rounded-xl p-7 my-3 border border-slate-100">
      <View className="bg-blue-50 rounded-2xl p-4 mb-4">
        <Icon name="chatbubbles-outline" color="#1877f2" size={28} />
      </View>
      <Text className="text-lg font-bold text-slate-900 text-center">
        {title}
      </Text>
      <Text className="text-slate-500 text-center leading-6 mt-2 mb-4">
        {detail}
      </Text>
      {action}
    </View>
  );
}
export function Sheet({
  title,
  visible,
  onClose,
  children,
  disabled = false,
}: {
  title: string;
  visible: boolean;
  onClose: () => void;
  children: ReactNode;
  disabled?: boolean;
}) {
  return (
    <Modal
      visible={visible}
      animationType="slide"
      presentationStyle="pageSheet"
      onRequestClose={() => {
        if (!disabled) onClose();
      }}
    >
      <SafeAreaView
        style={{ flex: 1, backgroundColor: "#f0f2f5" }}
        edges={["top", "bottom"]}
      >
        <View className="flex-row items-center justify-between px-5 py-4 border-b border-slate-100 bg-white">
          <Text className="font-bold text-lg text-slate-900 flex-1">
            {title}
          </Text>
          <Pressable
            accessibilityRole="button"
            accessibilityLabel="Đóng cửa sổ"
            disabled={disabled}
            onPress={onClose}
            className="bg-slate-100 p-2 rounded-full"
          >
            <Icon name="close" size={20} />
          </Pressable>
        </View>
        <KeyboardAvoidingView
          style={{ flex: 1 }}
          behavior={Platform.OS === "ios" ? "padding" : undefined}
        >
          {children}
        </KeyboardAvoidingView>
      </SafeAreaView>
    </Modal>
  );
}
export function relativeTime(value: string | null) {
  if (!value) return "Vừa xong";
  const minutes = Math.max(
    0,
    Math.floor((Date.now() - new Date(value).getTime()) / 60000),
  );
  if (minutes < 1) return "Vừa xong";
  if (minutes < 60) return `${minutes} phút`;
  if (minutes < 1440) return `${Math.floor(minutes / 60)} giờ`;
  return new Date(value).toLocaleDateString("vi-VN");
}
