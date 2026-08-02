import AsyncStorage from '@react-native-async-storage/async-storage';

interface CacheEntry<T> {
  data: T;
  expiresAt: number;
}

export async function getCached<T>(key: string): Promise<T | null> {
  try {
    const raw = await AsyncStorage.getItem(`cache:${key}`);
    if (!raw) return null;

    const entry: CacheEntry<T> = JSON.parse(raw);
    if (Date.now() > entry.expiresAt) {
      await AsyncStorage.removeItem(`cache:${key}`);
      return null;
    }

    return entry.data;
  } catch {
    return null;
  }
}

export async function setCached<T>(key: string, data: T, ttlMs: number): Promise<void> {
  try {
    const entry: CacheEntry<T> = { data, expiresAt: Date.now() + ttlMs };
    await AsyncStorage.setItem(`cache:${key}`, JSON.stringify(entry));
  } catch {
    // Silently ignore write errors — cache is best-effort
  }
}

export async function invalidateCache(key: string): Promise<void> {
  try {
    await AsyncStorage.removeItem(`cache:${key}`);
  } catch {}
}

export const TTL = {
  ONE_HOUR: 60 * 60 * 1000,
  THIRTY_MIN: 30 * 60 * 1000,
  FIVE_MIN: 5 * 60 * 1000,
};
