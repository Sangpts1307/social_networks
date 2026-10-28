import { useState } from "react";
import { ScrollView, Text, KeyboardAvoidingView, Platform } from "react-native";
import { auth } from "../services/auth";
import { Button, Field, Notice } from "../components/ui";
export function PasswordScreen({
  token,
  onDone,
  onExit,
}: {
  token: string;
  onDone: () => Promise<void>;
  onExit: () => void;
}) {
  const [current, setCurrent] = useState("");
  const [password, setPassword] = useState("");
  const [confirm, setConfirm] = useState("");
  const [error, setError] = useState("");
  const [busy, setBusy] = useState(false);
  async function submit() {
    if (password !== confirm) {
      setError("Mật khẩu xác nhận không khớp.");
      return;
    }
    setBusy(true);
    setError("");
    try {
      await auth.changePassword(token, current, password, confirm);
      await onDone();
    } catch (e) {
      setError(e instanceof Error ? e.message : "Không đổi được mật khẩu.");
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
        contentContainerStyle={{ padding: 24 }}
        keyboardShouldPersistTaps="handled"
      >
        <Text className="text-3xl font-bold text-slate-900 mb-3">
          Bảo vệ tài khoản
        </Text>
        <Text className="text-slate-500 mb-8">
          Bạn cần đổi mật khẩu lần đầu trước khi vào không gian làm việc.
        </Text>
        <Field
          label="Mật khẩu hiện tại"
          secureTextEntry
          value={current}
          onChangeText={setCurrent}
        />
        <Field
          label="Mật khẩu mới"
          secureTextEntry
          value={password}
          onChangeText={setPassword}
        />
        <Field
          label="Xác nhận mật khẩu mới"
          secureTextEntry
          value={confirm}
          onChangeText={setConfirm}
        />
        {!!error && <Notice message={error} />}
        <Button
          title="Cập nhật mật khẩu"
          busy={busy}
          onPress={() => void submit()}
        />
        <Text className="my-4" />
        <Button title="Đăng xuất" secondary onPress={onExit} disabled={busy} />
      </ScrollView>
    </KeyboardAvoidingView>
  );
}
