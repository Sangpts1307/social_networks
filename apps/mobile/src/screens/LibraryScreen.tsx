/* eslint-disable react-hooks/set-state-in-effect -- These effects synchronize loading state with external API requests and persisted sessions. */
import { useEffect, useState } from "react";
import {
  ActivityIndicator,
  FlatList,
  Text,
  TextInput,
  View,
} from "react-native";
import { getLibrary } from "../services/learning";
import { demoLibrary } from "../services/demo";
import type { KnowledgeItem } from "../types";
import { Icon, Notice } from "../components/ui";
export function LibraryScreen({
  token,
  demo,
}: {
  token: string;
  demo: boolean;
}) {
  const [items, setItems] = useState<KnowledgeItem[]>([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState("");
  const [query, setQuery] = useState("");
  const [revision, setRevision] = useState(0);
  useEffect(() => {
    let active = true;
    setLoading(true);
    setError("");
    const result = demo
      ? Promise.resolve({ data: demoLibrary })
      : getLibrary(token);
    result
      .then((value) => {
        if (active) setItems(value.data);
      })
      .catch((e) => {
        if (active)
          setError(e instanceof Error ? e.message : "Không tải được thư viện.");
      })
      .finally(() => {
        if (active) setLoading(false);
      });
    return () => {
      active = false;
    };
  }, [token, demo, revision]);
  return (
    <FlatList
      contentContainerStyle={{ padding: 20 }}
      data={items.filter((item) =>
        `${item.title} ${item.topic_name}`
          .toLocaleLowerCase("vi")
          .includes(query.toLocaleLowerCase("vi")),
      )}
      keyExtractor={(item) => item.id}
      refreshing={loading && items.length > 0}
      onRefresh={() => setRevision((v) => v + 1)}
      ListHeaderComponent={
        <View>
          <Text className="text-3xl font-bold text-slate-900 mb-2">
            Kho tri thức
          </Text>
          <Text className="text-slate-500 leading-6 mb-5">
            Kiến thức được chia sẻ. Giá trị được lan tỏa.
          </Text>
          <TextInput
            accessibilityLabel="Tìm tài liệu trong danh sách"
            placeholder="Tìm trong danh sách tài liệu..."
            value={query}
            onChangeText={setQuery}
            className="bg-white border border-slate-200 rounded-2xl p-4 mb-5 text-slate-900"
          />
          {!!error && (
            <Notice message={error} retry={() => setRevision((v) => v + 1)} />
          )}
        </View>
      }
      renderItem={({ item }) => (
        <View className="rounded-xl bg-white p-5 border border-slate-100 mb-4">
          <View className="flex-row items-center gap-2 mb-4">
            <Icon name="document-text-outline" color="#1877f2" />
            <Text className="text-xs font-semibold text-blue-600 flex-1">
              {item.topic_name} · {item.category_name || "Tài liệu"}
            </Text>
          </View>
          <Text className="text-lg font-bold text-slate-900 leading-6">
            {item.title}
          </Text>
          <Text className="text-slate-500 leading-6 mt-3">
            {item.summary || "Chưa có mô tả."}
          </Text>
        </View>
      )}
      ListEmptyComponent={
        loading ? (
          <ActivityIndicator color="#1877f2" />
        ) : (
          <Notice message="Không có tài liệu phù hợp." />
        )
      }
    />
  );
}
