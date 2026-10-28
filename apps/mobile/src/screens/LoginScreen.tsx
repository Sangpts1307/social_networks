import { useState } from "react";
import {
  KeyboardAvoidingView,
  Platform,
  ScrollView,
  Text,
  View,
} from "react-native";
import { auth } from "../services/auth";
import { API_URL } from "../services/api";
import type { Session } from "../types";
import { Button, Field, Icon, Notice } from "../components/ui";
export function LoginScreen({
  onLogin,
  onDemo,
}: {
  onLogin: (session: Session) => Promise<void>;
  onDemo: () => void;
}) {
  const [email, setEmail] = useState("");
  const [password, setPassword] = useState("");
  const [busy, setBusy] = useState(false);
  const [error, setError] = useState("");
  async function submit() {
    if (!email.trim() || !password) {
      setError("Vui lòng nhập email và mật khẩu.");
      return;
    }
    setBusy(true);
    setError("");
    try {
      await onLogin(await auth.login(email.trim(), password));
    } catch (e) {
      setError(e instanceof Error ? e.message : "Đăng nhập thất bại.");
    } finally {
      setBusy(false);
    }
  }
  return (
    <KeyboardAvoidingView
      style={{ flex: 1 }}
      behavior={Platform.OS === "ios" ? "padding" : undefined}
    >
      <ScrollView
        contentContainerStyle={{
          flexGrow: 1,
          justifyContent: "center",
          padding: 28,
        }}
        keyboardShouldPersistTaps="handled"
      >
        <View className="rounded-xl bg-blue-600 w-16 h-16 items-center justify-center mb-7">
          <Icon name="planet-outline" color="white" size={36} />
        </View>
        <Text className="text-blue-600 text-xs font-bold tracking-widest mb-3">
          OPENHELLO · WORK TOGETHER
        </Text>
        <Text className="text-4xl font-bold text-slate-900 leading-tight">
          Kết nối hôm nay.{"\n"}Vươn xa ngày mai.
        </Text>
        <Text className="text-base text-slate-500 leading-6 mt-4 mb-8">
          Không gian chung để chia sẻ, học hỏi và cùng đồng đội tạo nên những
          điều có ý nghĩa.
        </Text>
        <Field
          label="Email công ty"
          placeholder="ban@congty.com"
          value={email}
          onChangeText={setEmail}
          keyboardType="email-address"
          autoCapitalize="none"
          autoCorrect={false}
          autoComplete="email"
        />
        <Field
          label="Mật khẩu"
          placeholder="Nhập mật khẩu"
          value={password}
          onChangeText={setPassword}
          secureTextEntry
          autoComplete="current-password"
          onSubmitEditing={() => void submit()}
        />
        {!!error && <Notice message={error} />}
        <Button title="Đăng nhập" onPress={() => void submit()} busy={busy} />
        <View className="my-3" />
        <Button
          title="Khám phá bản demo"
          secondary
          disabled={busy}
          onPress={onDemo}
        />
        <Text className="text-xs text-slate-400 text-center mt-5 leading-5">
          Demo dùng dữ liệu mẫu, không cần tài khoản.{"\n"}Gateway: {API_URL}
        </Text>
      </ScrollView>
    </KeyboardAvoidingView>
  );
}
