import { useState } from "react";
import { Image, Text, View } from "react-native";
import { useVideoPlayer, VideoView } from "expo-video";
import type { MediaAttachment } from "../types";
function VideoPreview({ uri, tall }: { uri: string; tall: boolean }) {
  const player = useVideoPlayer(uri);
  return (
    <VideoView
      player={player}
      nativeControls
      contentFit="contain"
      style={{
        width: "100%",
        height: tall ? 400 : 240,
        backgroundColor: "#0f172a",
      }}
    />
  );
}
export function MediaPreview({
  media,
  tall = false,
}: {
  media: MediaAttachment;
  tall?: boolean;
}) {
  const [failed, setFailed] = useState(false);
  if (failed)
    return (
      <View className="bg-slate-100 p-6">
        <Text className="text-slate-500">
          Không tải được ảnh. Hãy chọn lại tệp.
        </Text>
      </View>
    );
  if (media.type === "video")
    return <VideoPreview uri={media.uri} tall={tall} />;
  return (
    <Image
      accessibilityLabel={media.fileName || "Ảnh đã chọn"}
      source={{ uri: media.uri }}
      resizeMode={tall ? "contain" : "cover"}
      onError={() => setFailed(true)}
      style={{
        width: "100%",
        height: tall ? 400 : 240,
        backgroundColor: "#e2e8f0",
      }}
    />
  );
}
