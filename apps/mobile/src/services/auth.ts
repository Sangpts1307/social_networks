import { request } from "./api";
import type { Session, User } from "../types";
export const auth = {
  login: (email: string, password: string) =>
    request<Session>("/v1/auth/login", undefined, "POST", { email, password }),
  inspect: (token: string) =>
    request<{ user: User }>("/v1/auth/session", token),
  logout: (token: string) =>
    request<void>("/v1/auth/logout", token, "POST", {}),
  changePassword: (
    token: string,
    current_password: string,
    password: string,
    password_confirmation: string,
  ) =>
    request("/v1/auth/password", token, "POST", {
      current_password,
      password,
      password_confirmation,
    }),
};
