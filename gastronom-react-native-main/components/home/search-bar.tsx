import React, { useRef, useState } from 'react';
import { TextInput, TouchableOpacity, View, StyleSheet } from 'react-native';
import { useRouter } from 'expo-router';
import { IconSymbol } from '@/components/ui/icon-symbol';
import { Colors } from '@/constants/theme';
import { useColorScheme } from '@/hooks/use-color-scheme';
import { addSearchQuery } from '@/services/search-history';

interface SearchBarProps {
  initialQuery?: string;
  onSearch?: (query: string) => void;
}

export function SearchBar({ initialQuery = '', onSearch }: SearchBarProps) {
  const colorScheme = useColorScheme() ?? 'light';
  const colors = Colors[colorScheme];
  const router = useRouter();
  const [query, setQuery] = useState(initialQuery);
  const inputRef = useRef<TextInput>(null);

  const handleSubmit = async () => {
    const trimmed = query.trim();
    if (!trimmed) return;
    await addSearchQuery(trimmed);
    if (onSearch) {
      onSearch(trimmed);
    } else {
      router.push({ pathname: '/(tabs)/search', params: { query: trimmed } });
    }
  };

  return (
    <View style={styles.container}>
      <View style={[styles.searchWrapper, { backgroundColor: colors.surface }]}>
        <TouchableOpacity style={styles.searchIcon} onPress={() => inputRef.current?.focus()}>
          <IconSymbol name="magnifyingglass" size={24} color={colors.primary} />
        </TouchableOpacity>
        <TextInput
          ref={inputRef}
          style={[styles.input, { color: colors.text }]}
          placeholder="Яблоки, молоко, хлеб..."
          placeholderTextColor={colors.textSub}
          value={query}
          onChangeText={setQuery}
          returnKeyType="search"
          onSubmitEditing={handleSubmit}
        />
        <TouchableOpacity style={styles.filterIcon} onPress={handleSubmit}>
          <IconSymbol name="magnifyingglass" size={22} color={colors.textSub} />
        </TouchableOpacity>
      </View>
    </View>
  );
}

const styles = StyleSheet.create({
  container: {
    paddingHorizontal: 16,
    paddingVertical: 8,
  },
  searchWrapper: {
    flexDirection: 'row',
    alignItems: 'center',
    borderRadius: 12,
    paddingHorizontal: 12,
    height: 48,
    shadowColor: '#000',
    shadowOffset: { width: 0, height: 1 },
    shadowOpacity: 0.1,
    shadowRadius: 2,
    elevation: 2,
  },
  searchIcon: {
    marginRight: 8,
  },
  input: {
    flex: 1,
    fontSize: 14,
    height: '100%',
    paddingLeft: 10,
  },
  filterIcon: {
    padding: 4,
  },
});
