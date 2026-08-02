import AsyncStorage from '@react-native-async-storage/async-storage';

const KEY = 'search:history';
const MAX = 10;

export async function getSearchHistory(): Promise<string[]> {
  try {
    const raw = await AsyncStorage.getItem(KEY);
    return raw ? JSON.parse(raw) : [];
  } catch {
    return [];
  }
}

export async function addSearchQuery(query: string): Promise<void> {
  const trimmed = query.trim();
  if (!trimmed) return;
  try {
    const current = await getSearchHistory();
    const deduped = current.filter(q => q.toLowerCase() !== trimmed.toLowerCase());
    const updated = [trimmed, ...deduped].slice(0, MAX);
    await AsyncStorage.setItem(KEY, JSON.stringify(updated));
  } catch {}
}

export async function clearSearchHistory(): Promise<void> {
  try {
    await AsyncStorage.removeItem(KEY);
  } catch {}
}
