import { request } from "./api";
import type { KnowledgeItem } from "../types";
export const getLibrary = (token: string) =>
  request<{ data: KnowledgeItem[] }>("/v1/learning/knowledge-items", token);
