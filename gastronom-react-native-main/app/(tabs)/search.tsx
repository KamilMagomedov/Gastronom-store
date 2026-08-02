import { ProductCard } from '@/components/home/product-card';
import { SearchBar } from '@/components/home/search-bar';
import { SearchHistory } from '@/components/home/search-history';
import { IconSymbol } from '@/components/ui/icon-symbol';
import { Colors } from '@/constants/theme';
import { useColorScheme } from '@/hooks/use-color-scheme';
import { ApiProduct, ApiService } from '@/services/api';
import { addSearchQuery } from '@/services/search-history';
import { useLocalSearchParams, useRouter } from 'expo-router';
import React, { useCallback, useEffect, useRef, useState } from 'react';
import {
    ActivityIndicator,
    FlatList,
    Platform,
    StyleSheet,
    Text,
    TouchableOpacity,
    View,
} from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';

const LIMIT = 20;

function mapProduct(p: ApiProduct) {
  return {
    id: String(p.id),
    slug: p.slug,
    name: p.name,
    price: parseFloat(p.price),
    oldPrice: p.old_price ? parseFloat(p.old_price) : undefined,
    discount: p.old_price
      ? `-${Math.round((1 - parseFloat(p.price) / parseFloat(p.old_price)) * 100)}%`
      : undefined,
    unit: p.unit,
    image: p.image,
  };
}

export default function SearchScreen() {
  const colorScheme = useColorScheme() ?? 'light';
  const colors = Colors[colorScheme];
  const router = useRouter();
  const { query: paramQuery } = useLocalSearchParams<{ query?: string }>();

  const [activeQuery, setActiveQuery] = useState(paramQuery ?? '');
  const [products, setProducts] = useState<ApiProduct[]>([]);
  const [loading, setLoading] = useState(false);
  const [loadingMore, setLoadingMore] = useState(false);
  const [page, setPage] = useState(1);
  const [hasMore, setHasMore] = useState(false);
  const [total, setTotal] = useState(0);

  // Track the query that was last searched so we can paginate correctly
  const searchedQueryRef = useRef('');

  const doSearch = useCallback(async (q: string, pageNum: number) => {
    const trimmed = q.trim();
    if (!trimmed) return;

    if (pageNum === 1) {
      setLoading(true);
      setProducts([]);
    } else {
      setLoadingMore(true);
    }

    try {
      console.log('Search: Searching for:', trimmed, 'page:', pageNum);
      const res = await ApiService.searchProducts(trimmed, pageNum, LIMIT);
      console.log('Search: Found', res.data?.length || 0, 'products, total:', res.paginator?.total || 0);
      if (pageNum === 1) {
        setProducts(res.data);
        setPage(1);
      } else {
        setProducts(prev => [...prev, ...res.data]);
      }
      setHasMore(res.paginator.has_more);
      setTotal(res.paginator.total);
      searchedQueryRef.current = trimmed;
    } catch (error) {
      console.error('Search: Failed to search products:', error);
      // ignore errors — search is best-effort, but log them
      if (pageNum === 1) {
        setProducts([]);
      }
    } finally {
      setLoading(false);
      setLoadingMore(false);
    }
  }, []);

  // Run search whenever the param query changes (e.g. user taps history item)
  useEffect(() => {
    if (paramQuery?.trim()) {
      setActiveQuery(paramQuery);
      doSearch(paramQuery, 1);
    }
  }, [paramQuery]);

  const handleSearch = async (q: string) => {
    setActiveQuery(q);
    await addSearchQuery(q);
    doSearch(q, 1);
  };

  const handleLoadMore = () => {
    if (loadingMore || !hasMore) return;
    const nextPage = page + 1;
    setPage(nextPage);
    doSearch(searchedQueryRef.current, nextPage);
  };

  const renderFooter = () => {
    if (!loadingMore) return null;
    return (
      <View style={styles.footerLoader}>
        <ActivityIndicator color={colors.primary} />
      </View>
    );
  };

  const showEmpty = !loading && activeQuery.trim() && products.length === 0;

  const listHeader = (
    <>
      <SearchHistory />
      {products.length > 0 && (
        <Text style={[styles.resultsTitle, { color: colors.text }]}>
          Найдено {total} товаров
        </Text>
      )}
    </>
  );

  return (
    <SafeAreaView style={[styles.container, { backgroundColor: colors.background }]} edges={['top']}>
      {/* Header */}
      <View style={[styles.header, { backgroundColor: colors.surface, borderBottomColor: colors.border }]}>
        <TouchableOpacity style={styles.backBtn} onPress={() => router.back()}>
          <IconSymbol name="chevron.left" size={24} color={colors.text} />
        </TouchableOpacity>
        <View style={styles.searchBarWrap}>
          <SearchBar initialQuery={activeQuery} onSearch={handleSearch} />
        </View>
      </View>

      {/* Loading indicator (first page) */}
      {loading && (
        <>
          <SearchHistory />
          <View style={styles.centerFill}>
            <ActivityIndicator color={colors.primary} size="large" />
          </View>
        </>
      )}

      {/* Empty results */}
      {showEmpty && (
        <>
          <SearchHistory />
          <View style={styles.centerFill}>
            <IconSymbol name="magnifyingglass" size={48} color={colors.textSub} />
            <Text style={[styles.emptyTitle, { color: colors.text }]}>Ничего не найдено</Text>
            <Text style={[styles.emptySubtitle, { color: colors.textSub }]}>
              Попробуйте другой запрос
            </Text>
          </View>
        </>
      )}

      {/* Results */}
      {!loading && products.length > 0 && (
        <FlatList
          data={products}
          keyExtractor={(item) => String(item.id)}
          numColumns={2}
          contentContainerStyle={styles.listContent}
          columnWrapperStyle={styles.row}
          onEndReached={handleLoadMore}
          onEndReachedThreshold={0.3}
          ListHeaderComponent={listHeader}
          ListFooterComponent={renderFooter}
          renderItem={({ item }) => (
            <ProductCard product={mapProduct(item)} style={styles.cardFill} />
          )}
        />
      )}

      {/* No query yet, not loading — show history only */}
      {!loading && !showEmpty && products.length === 0 && (
        <SearchHistory />
      )}
    </SafeAreaView>
  );
}

const styles = StyleSheet.create({
  container: { flex: 1 },
  header: {
    flexDirection: 'row',
    alignItems: 'center',
    borderBottomWidth: 1,
    paddingRight: 8,
    ...Platform.select({
      android: { paddingTop: 8 },
    }),
  },
  backBtn: {
    width: 48,
    height: 48,
    alignItems: 'center',
    justifyContent: 'center',
    marginLeft: 4,
  },
  searchBarWrap: { flex: 1 },
  centerFill: {
    flex: 1,
    alignItems: 'center',
    justifyContent: 'center',
    gap: 12,
    padding: 32,
  },
  emptyTitle: { fontSize: 18, fontWeight: '700' },
  emptySubtitle: { fontSize: 14, textAlign: 'center' },
  listContent: { padding: 16, paddingBottom: 100 },
  row: { gap: 12, marginBottom: 12 },
  cardFill: { flex: 1, width: undefined },
  resultsTitle: {
    fontSize: 16,
    fontWeight: '700',
    marginBottom: 16,
  },
  footerLoader: {
    paddingVertical: 20,
    alignItems: 'center',
  },
});
