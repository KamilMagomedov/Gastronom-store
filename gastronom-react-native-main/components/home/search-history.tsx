import React, { useCallback, useState } from 'react';
import { View, Text, StyleSheet, TouchableOpacity } from 'react-native';
import { useFocusEffect, useRouter } from 'expo-router';
import { Colors } from '@/constants/theme';
import { useColorScheme } from '@/hooks/use-color-scheme';
import { IconSymbol } from '@/components/ui/icon-symbol';
import { addSearchQuery, clearSearchHistory, getSearchHistory } from '@/services/search-history';

export function SearchHistory() {
  const colorScheme = useColorScheme() ?? 'light';
  const colors = Colors[colorScheme];
  const router = useRouter();
  const [history, setHistory] = useState<string[]>([]);

  const loadHistory = useCallback(async () => {
    const items = await getSearchHistory();
    setHistory(items);
  }, []);
  
  useFocusEffect(
    useCallback(() => {
      loadHistory();
    }, [loadHistory])
  );

  const handleItemPress = async (query: string) => {
    await addSearchQuery(query); // move tapped item to top
    router.push({ pathname: '/(tabs)/search', params: { query } });
  };

  const handleClear = async () => {
    await clearSearchHistory();
    setHistory([]);
  };

  if (history.length === 0) return null;

  return (
    <View style={styles.container}>
      <View style={styles.header}>
        <Text style={[styles.title, { color: colors.textSub }]}>История поиска</Text>
        <TouchableOpacity onPress={handleClear}>
          <Text style={[styles.clearButton, { color: colors.primary }]}>Очистить</Text>
        </TouchableOpacity>
      </View>
      <View style={styles.chipsContainer}>
        {history.map((item) => (
          <TouchableOpacity
            key={item}
            style={[styles.chip, { backgroundColor: colors.surface, borderColor: colors.border }]}
            onPress={() => handleItemPress(item)}
          >
            <IconSymbol name="clock.arrow.circlepath" size={16} color={colors.textSub} style={styles.icon} />
            <Text style={[styles.chipText, { color: colors.text }]}>{item}</Text>
          </TouchableOpacity>
        ))}
      </View>
    </View>
  );
}

const styles = StyleSheet.create({
  container: {
    paddingHorizontal: 16,
    paddingVertical: 12,
  },
  header: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    marginBottom: 12,
  },
  title: {
    fontSize: 10,
    fontWeight: '700',
    textTransform: 'uppercase',
    letterSpacing: 1,
  },
  clearButton: {
    fontSize: 12,
    fontWeight: '600',
  },
  chipsContainer: {
    flexDirection: 'row',
    flexWrap: 'wrap',
    gap: 8,
  },
  chip: {
    flexDirection: 'row',
    alignItems: 'center',
    paddingHorizontal: 12,
    paddingVertical: 8,
    borderRadius: 8,
    borderWidth: 1,
  },
  icon: {
    marginRight: 6,
  },
  chipText: {
    fontSize: 14,
    fontWeight: '500',
  },
});
