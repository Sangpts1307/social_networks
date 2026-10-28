import { request } from "./api";
import type {
  Post,
  Channel,
  Page,
  Comment,
  Reaction,
  SocialFilter,
} from "../types";
const base = "/v1/social";
const path = (id: string) => `${base}/posts/${encodeURIComponent(id)}`;
function query(values: Record<string, string | undefined>) {
  const params = Object.entries(values)
    .filter((entry): entry is [string, string] => entry[1] !== undefined)
    .map(([key, value]) => `${key}=${encodeURIComponent(value)}`);
  return params.length ? `?${params.join("&")}` : "";
}
export const postsApi = {
  list: (token: string, filter: SocialFilter = {}, cursor?: string) =>
    request<Page<Post>>(`${base}/posts${query({ ...filter, cursor })}`, token),
  channels: (token: string, cursor?: string) =>
    request<Page<Channel>>(`${base}/channels${query({ cursor })}`, token),
  create: (token: string, channel_id: string, body_markdown: string) =>
    request<{ data: Post }>(`${base}/posts`, token, "POST", {
      channel_id,
      body_markdown,
      post_type: "update",
      publication_status: "published",
    }),
  update: (token: string, id: string, body_markdown: string) =>
    request<{ data: Post }>(path(id), token, "PATCH", { body_markdown }),
  remove: (token: string, id: string) =>
    request<void>(path(id), token, "DELETE"),
  react: (token: string, id: string, reaction: Reaction | null) =>
    request(
      `${path(id)}/reaction`,
      token,
      reaction ? "PUT" : "DELETE",
      reaction ? { reaction_type: reaction } : undefined,
    ),
  save: (token: string, id: string, saved: boolean) =>
    request(`${path(id)}/save`, token, saved ? "PUT" : "DELETE"),
  comments: (token: string, id: string, cursor?: string) =>
    request<Page<Comment>>(`${path(id)}/comments${query({ cursor })}`, token),
  comment: (
    token: string,
    id: string,
    body_markdown: string,
    parent_comment_id?: string,
  ) =>
    request<{ data: Comment }>(`${path(id)}/comments`, token, "POST", {
      body_markdown,
      ...(parent_comment_id ? { parent_comment_id } : {}),
    }),
};
