import type { ComponentProps } from "react";
import {
  ActivityIndicator,
  Pressable,
  Text,
  TextInput,
  View,
} from "react-native";
import { Ionicons } from "@expo/vector-icons";
export function Button({
  title,
  onPress,
  busy = false,
  secondary = false,
  disabled = false,
}: {
  title: string;
  onPress: () => void;
  busy?: boolean;
  secondary?: boolean;
  disabled?: boolean;
}) {
  return (
    <Pressable
      accessibilityRole="button"
      disabled={busy || disabled}
      onPress={onPress}
      className={`rounded-2xl px-5 py-4 items-center ${secondary ? "bg-blue-50" : "bg-blue-600"} ${busy || disabled ? "opacity-50" : ""}`}
    >
      {busy ? (
        <ActivityIndicator color={secondary ? "#1877f2" : "white"} />
      ) : (
        <Text
          className={`font-semibold text-base ${secondary ? "text-blue-700" : "text-white"}`}
        >
          {title}
        </Text>
      )}
    </Pressable>
  );
}
export function Field({
  label,
  ...props
}: ComponentProps<typeof TextInput> & { label: string }) {
  return (
    <View className="mb-4">
      <Text className="text-slate-600 mb-2 font-medium">{label}</Text>
      <TextInput
        accessibilityLabel={label}
        placeholderTextColor="#94a3b8"
        className="rounded-2xl border border-slate-200 bg-white px-4 py-4 text-base text-slate-900"
        {...props}
      />
    </View>
  );
}
export function Notice({
  message,
  retry,
}: {
  message: string;
  retry?: () => void;
}) {
  return (
    <View className="rounded-2xl bg-amber-50 border border-amber-100 p-4 my-3">
      <Text accessibilityRole="alert" className="text-amber-900 leading-5">
        {message}
      </Text>
      {retry && (
        <Pressable accessibilityRole="button" onPress={retry}>
          <Text className="font-bold text-blue-600 mt-3">Thử lại</Text>
        </Pressable>
      )}
    </View>
  );
}
export function Icon({
  name,
  color = "#64748b",
  size = 22,
}: {
  name: ComponentProps<typeof Ionicons>["name"];
  color?: string;
  size?: number;
}) {
  return <Ionicons name={name} size={size} color={color} />;
}
export function Avatar({ name }: { name: string }) {
  return (
    <View className="h-11 w-11 rounded-2xl bg-blue-100 items-center justify-center">
      <Text className="font-bold text-blue-700 text-lg">
        {name.trim().charAt(0).toUpperCase()}
      </Text>
    </View>
  );
}
