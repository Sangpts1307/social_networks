import { ScrollView, Text, View } from "react-native";
import type { User } from "../types";
import { Avatar, Button, Icon, Notice } from "../components/ui";
export function ProfileScreen({
  user,
  demo,
  busy,
  message,
  onLogout,
}: {
  user: User;
  demo: boolean;
  busy: boolean;
  message: string;
  onLogout: () => void;
}) {
  return (
    <ScrollView contentContainerStyle={{ padding: 24 }}>
      <Text className="text-3xl font-bold text-slate-900 mb-6">
        Hồ sơ của bạn
      </Text>
      <View className="bg-white border border-slate-100 rounded-xl p-6 items-center">
        <Avatar name={user.display_name} />
        <Text className="text-xl font-bold text-slate-900 mt-4">
          {user.display_name}
        </Text>
        <Text className="text-slate-500 mt-2">{user.email}</Text>
        <View className="bg-emerald-50 rounded-full px-4 py-2 mt-5">
          <Text className="text-emerald-700 text-xs font-semibold">
            {demo ? "Tài khoản minh họa" : "Đã xác thực qua Gateway"}
          </Text>
        </View>
      </View>
      <View className="rounded-xl bg-blue-50 p-5 my-5">
        <Icon name="shield-checkmark-outline" color="#1877f2" />
        <Text className="font-bold text-blue-900 mt-3">
          Không gian dành cho đội ngũ
        </Text>
        <Text className="text-blue-700 leading-6 mt-2">
          {demo
            ? "Các tương tác chỉ diễn ra trong phiên demo. Đăng nhập để sử dụng dữ liệu thực."
            : "Phiên đăng nhập được lưu bằng kho bảo mật của thiết bị. Quyền truy cập do hệ thống quản lý."}
        </Text>
      </View>
      {!!message && <Notice message={message} />}
      <Button
        title={demo ? "Thoát bản demo" : "Đăng xuất"}
        busy={busy}
        secondary
        onPress={onLogout}
      />
    </ScrollView>
  );
}
