import Constants from "expo-constants";
import { Platform } from "react-native";
import * as SecureStore from "expo-secure-store";

const host = Constants.expoConfig?.hostUri?.split(":")[0];
export const API_URL = (
  process.env.EXPO_PUBLIC_API_URL ||
  `http://${host || (Platform.OS === "android" ? "10.0.2.2" : "127.0.0.1")}:8080/api`
).replace(/\/$/, "");
export class ApiError extends Error {
  constructor(
    message: string,
    public status: number,
    public code?: string,
  ) {
    super(message);
  }
}
let expired: (() => void) | undefined;
export function onExpired(callback: () => void) {
  expired = callback;
  return () => {
    expired = undefined;
  };
}
export async function request<T>(
  path: string,
  token?: string,
  method = "GET",
  payload?: unknown,
): Promise<T> {
  const controller = new AbortController();
  const timer = setTimeout(() => controller.abort(), 15000);
  try {
    const response = await fetch(`${API_URL}${path}`, {
      method,
      signal: controller.signal,
      headers: {
        Accept: "application/json",
        ...(token ? { Authorization: `Bearer ${token}` } : {}),
        ...(payload !== undefined
          ? { "Content-Type": "application/json" }
          : {}),
      },
      ...(payload !== undefined ? { body: JSON.stringify(payload) } : {}),
    });
    const body =
      response.status === 204
        ? undefined
        : await response.json().catch(() => undefined);
    if (!response.ok) {
      if (response.status === 401 && token) expired?.();
      const validation = body?.errors
        ? Object.values(body.errors).flat()[0]
        : undefined;
      throw new ApiError(
        String(
          validation ||
            body?.message ||
            `Yêu cầu thất bại (${response.status}).`,
        ),
        response.status,
        body?.code,
      );
    }
    if (response.status !== 204 && body === undefined)
      throw new ApiError("Máy chủ trả dữ liệu không hợp lệ.", 502);
    return body as T;
  } catch (error) {
    if (error instanceof ApiError) throw error;
    throw new ApiError(
      "Không kết nối được Gateway. Kiểm tra địa chỉ máy chủ và mạng Wi-Fi.",
      0,
    );
  } finally {
    clearTimeout(timer);
  }
}
const key = "openhello.mobile.token";
let webToken: string | null = null;
// Browser preview keeps credentials in memory; native devices use SecureStore.
export const tokenStore = {
  get: () =>
    Platform.OS === "web"
      ? Promise.resolve(webToken)
      : SecureStore.getItemAsync(key),
  set: async (token: string) => {
    if (Platform.OS === "web") webToken = token;
    else await SecureStore.setItemAsync(key, token);
  },
  clear: async () => {
    if (Platform.OS === "web") webToken = null;
    else await SecureStore.deleteItemAsync(key);
  },
};
