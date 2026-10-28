/* eslint-disable react-hooks/set-state-in-effect -- These effects synchronize loading state with external API requests and persisted sessions. */
import "./global.css";
import { useEffect, useState } from "react";
import { ActivityIndicator, Text, View } from "react-native";
import { SafeAreaProvider, SafeAreaView } from "react-native-safe-area-context";
import { StatusBar } from "expo-status-bar";
import { auth } from "./src/services/auth";
import { ApiError, onExpired, tokenStore } from "./src/services/api";
import { demoUser } from "./src/services/demo";
import type { User, Session } from "./src/types";
import { LoginScreen } from "./src/screens/LoginScreen";
import { FeedScreen } from "./src/screens/FeedScreen";
import { PasswordScreen } from "./src/screens/PasswordScreen";
import { Button, Notice } from "./src/components/ui";
function MobileApp() {
  const [user, setUser] = useState<User | null>(null);
  const [token, setToken] = useState("");
  const [demo, setDemo] = useState(false);
  const [booting, setBooting] = useState(true);
  const [restoreError, setRestoreError] = useState("");
  const [revision, setRevision] = useState(0);
  const [loggingOut, setLoggingOut] = useState(false);
  const [logoutError, setLogoutError] = useState("");
  useEffect(
    () =>
      onExpired(() => {
        setUser(null);
        setToken("");
        void tokenStore.clear().catch(() => {});
      }),
    [],
  );
  useEffect(() => {
    let active = true;
    setBooting(true);
    setRestoreError("");
    async function restore() {
      try {
        const saved = await tokenStore.get();
        if (!saved) return;
        const result = await auth.inspect(saved);
        if (active) {
          setToken(saved);
          setUser(result.user);
        }
      } catch (e) {
        if (e instanceof ApiError && e.status === 401) await tokenStore.clear();
        else if (active)
          setRestoreError(
            e instanceof Error ? e.message : "Không khôi phục được phiên.",
          );
      } finally {
        if (active) setBooting(false);
      }
    }
    void restore();
    return () => {
      active = false;
    };
  }, [revision]);
  async function login(session: Session) {
    await tokenStore.set(session.access_token);
    setToken(session.access_token);
    setUser(session.user);
    setDemo(false);
  }
  async function logout() {
    setLoggingOut(true);
    setLogoutError("");
    try {
      if (!demo) await auth.logout(token);
      await tokenStore.clear();
      setToken("");
      setUser(null);
      setDemo(false);
    } catch (e) {
      setLogoutError(
        e instanceof Error
          ? e.message
          : "Chưa đăng xuất được. Vui lòng thử lại.",
      );
    } finally {
      setLoggingOut(false);
    }
  }
  if (booting)
    return (
      <View className="flex-1 items-center justify-center">
        <ActivityIndicator color="#1877f2" />
        <Text className="text-slate-500 mt-4">Đang mở không gian của bạn…</Text>
      </View>
    );
  if (restoreError && !user)
    return (
      <View className="flex-1 justify-center p-6">
        <Notice
          message={restoreError}
          retry={() => setRevision((v) => v + 1)}
        />
        <Button
          title="Về đăng nhập"
          onPress={() => {
            void tokenStore.clear().then(() => setRestoreError(""));
          }}
        />
      </View>
    );
  if (!user)
    return (
      <LoginScreen
        onLogin={login}
        onDemo={() => {
          setDemo(true);
          setUser(demoUser);
        }}
      />
    );
  if (user.must_change_password)
    return (
      <View className="flex-1">
        {!!logoutError && <Notice message={logoutError} />}
        <PasswordScreen
          token={token}
          onDone={async () => {
            const result = await auth.inspect(token);
            setUser(result.user);
          }}
          onExit={() => void logout()}
        />
      </View>
    );
  return (
    <FeedScreen
      key={demo ? "demo-social" : user.id}
      token={token}
      user={user}
      demo={demo}
      onLogout={() => void logout()}
      loggingOut={loggingOut}
      logoutError={logoutError}
    />
  );
}
export default function App() {
  return (
    <SafeAreaProvider>
      <SafeAreaView
        edges={["top", "bottom"]}
        style={{ flex: 1, backgroundColor: "#f0f2f5" }}
      >
        <StatusBar style="dark" />
        <MobileApp />
      </SafeAreaView>
    </SafeAreaProvider>
  );
}
