export function normalizeSearch(value: string): string {
  return value
    .normalize("NFD")
    .replace(/[\u0300-\u036f]/g, "")
    .replace(/đ/g, "d")
    .replace(/Đ/g, "D")
    .toLowerCase()
    .trim();
}
export function matchesSearch(value: string, query: string): boolean {
  return normalizeSearch(query)
    .split(/\s+/)
    .every((word) => normalizeSearch(value).includes(word));
}
