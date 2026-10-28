export type User = {
  id: string;
  organization_id: string;
  email: string;
  display_name: string;
  must_change_password: boolean;
};
export type Session = { access_token: string; expires_at: string; user: User };
export type Post = {
  demo_media?: MediaAttachment[];
  demo_image_url?: string;
  id: string;
  channel_id: string;
  author_user_id: string;
  title: string | null;
  body_plaintext: string;
  published_at: string | null;
  reaction_count: number;
  comment_count: number;
  my_reaction: string | null;
  is_saved: boolean | number;
  post_type?: "update" | "news" | "announcement";
  edited_at?: string | null;
  comments_locked_at?: string | null;
};
export type Channel = {
  id: string;
  name: string;
  description?: string | null;
  membership_policy?: "open" | "managed";
  visibility?: string;
};
export type Reaction = "like" | "love" | "sad" | "angry";
export type Comment = {
  id: string;
  post_id: string;
  parent_comment_id: string | null;
  author_user_id: string;
  body_plaintext: string;
  created_at: string;
  edited_at?: string | null;
};
export type SocialFilter = {
  channel_id?: string;
  saved?: "1";
  author_user_id?: string;
};
export type Member = {
  avatar?: string;
  name: string;
  role: string;
  initials: string;
  color: string;
};
export type Page<T> = { data: T[]; meta: { next_cursor: string | null } };
export type KnowledgeItem = {
  id: string;
  title: string;
  summary: string | null;
  topic_name: string;
  category_name: string | null;
};

export type MediaAttachment = {
  id: string;
  uri: string;
  type: "image" | "video";
  fileName?: string;
  duration?: number;
};
export type Story = {
  id: string;
  author_user_id: string;
  body: string;
  media: MediaAttachment[];
  background: string;
  created_at: string;
  expires_at: string;
};
export type ComposerMode = "post" | "story";
