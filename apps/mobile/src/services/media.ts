import * as ImagePicker from "expo-image-picker";
import { Platform } from "react-native";
import type { MediaAttachment } from "../types";
export async function pickMedia(limit: number): Promise<MediaAttachment[]> {
  if (Platform.OS === "ios") {
    const permission = await ImagePicker.requestMediaLibraryPermissionsAsync();
    if (!permission.granted)
      throw new Error(
        "Hãy cho phép truy cập ảnh/video trong cài đặt để chọn nội dung.",
      );
  }
  const result = await ImagePicker.launchImageLibraryAsync({
    mediaTypes: ["images", "videos"],
    allowsMultipleSelection: limit > 1,
    selectionLimit: limit,
    quality: 0.85,
  });
  if (result.canceled) return [];
  if (result.assets.length > limit)
    throw new Error(`Bạn có thể chọn tối đa ${limit} tệp ở lần này.`);
  return result.assets.map((asset, index) => {
    if (asset.fileSize && asset.fileSize > 100 * 1024 * 1024)
      throw new Error("Mỗi ảnh/video cần nhỏ hơn 100 MB để chạy demo ổn định.");
    return {
      id: `media-${Date.now()}-${index}`,
      uri: asset.uri,
      type: asset.type === "video" ? "video" : "image",
      fileName: asset.fileName || undefined,
      duration: asset.duration || undefined,
    };
  });
}
