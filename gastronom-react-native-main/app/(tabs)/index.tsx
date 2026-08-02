import React, { useEffect, useState, useCallback } from 'react';
import { StyleSheet, ScrollView, View, Text, TouchableOpacity, ActivityIndicator, RefreshControl } from 'react-native';
import { ThemedView } from '@/components/themed-view';
import { Colors } from '@/constants/theme';
import { useColorScheme } from '@/hooks/use-color-scheme';
import { HomeHeader } from '@/components/home/home-header';
import { SearchBar } from '@/components/home/search-bar';
import { SearchHistory } from '@/components/home/search-history';
import { CategoryList } from '@/components/home/category-list';
import { ProductCard } from '@/components/home/product-card';
import { ApiService, ApiCategory, ApiProduct } from '@/services/api';
import { getCached, setCached, invalidateCache, TTL } from '@/services/cache';

const CACHE_KEY_FREQUENT = 'home:frequently-purchased';

// Map API product to ProductCard's expected shape
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

export default function HomeScreen() {
  const colorScheme = useColorScheme() ?? 'light';
  const colors = Colors[colorScheme];

  const [categories, setCategories] = useState<ApiCategory[]>([]);
  const [frequentProducts, setFrequentProducts] = useState<ApiProduct[]>([]);
  const [popularProducts, setPopularProducts] = useState<ApiProduct[]>([]);
  const [selectedCategoryId, setSelectedCategoryId] = useState<number | null>(null);

  const [loadingCategories, setLoadingCategories] = useState(true);
  const [loadingFrequent, setLoadingFrequent] = useState(true);
  const [loadingPopular, setLoadingPopular] = useState(true);
  const [refreshing, setRefreshing] = useState(false);

  const fetchAll = useCallback(async () => {
    await Promise.allSettled([
      ApiService.getCategories()
        .then((res) => setCategories(res.data))
        .finally(() => setLoadingCategories(false)),
      (async () => {
        try {
          const cached = await getCached<ApiProduct[]>(CACHE_KEY_FREQUENT);
          if (cached) {
            setFrequentProducts(cached);
            return;
          }
          const res = await ApiService.getFrequentlyPurchased();
          setFrequentProducts(res.data);
          await setCached(CACHE_KEY_FREQUENT, res.data, TTL.ONE_HOUR);
        } finally {
          setLoadingFrequent(false);
        }
      })(),
      ApiService.getPopularProducts()
        .then((res) => setPopularProducts(res.data))
        .finally(() => setLoadingPopular(false)),
    ]);
  }, []);

  useEffect(() => {
    fetchAll();
  }, [fetchAll]);

  const onRefresh = useCallback(async () => {
    setRefreshing(true);
    setLoadingCategories(true);
    setLoadingFrequent(true);
    setLoadingPopular(true);
    await invalidateCache(CACHE_KEY_FREQUENT);
    await fetchAll();
    setRefreshing(false);
  }, [fetchAll]);

  // Filter popular products by selected category
  const filteredPopular = selectedCategoryId === null
    ? popularProducts
    : popularProducts.filter((p) => p.category.id === selectedCategoryId);

    console.log('frequentProducts: ', frequentProducts)
  return (
    <ThemedView style={[styles.container, { backgroundColor: colors.background }]}>
      <HomeHeader />
      <ScrollView
        showsVerticalScrollIndicator={false}
        refreshControl={
          <RefreshControl
            refreshing={refreshing}
            onRefresh={onRefresh}
            tintColor={colors.primary}
            colors={[colors.primary]}
          />
        }
      >
        <View style={styles.searchSection}>
          <SearchBar />
          <SearchHistory />
        </View>

        {/* Часто покупаемые */}
        <Section title="Часто покупаемые">
          {loadingFrequent ? (
            <ActivityIndicator color={colors.primary} style={styles.loader} />
          ) : frequentProducts.length === 0 ? (
            <Text style={[styles.emptyText, { color: colors.textSub }]}>Нет товаров</Text>
          ) : (
            <View style={styles.productList}>
              {frequentProducts.map((item) => (
                <ProductCard key={item.id} product={mapProduct(item)} horizontal />
              ))}
            </View>
          )}
        </Section>

        {/* Категории */}
        <Section title="Категории" hideAll>
          <CategoryList
            categories={categories}
            selectedId={selectedCategoryId}
            onSelect={setSelectedCategoryId}
            loading={loadingCategories}
          />
        </Section>

        {/* Популярные предложения */}
        <Section title="Популярные предложения">
          {loadingPopular ? (
            <ActivityIndicator color={colors.primary} style={styles.loader} />
          ) : filteredPopular.length === 0 ? (
            <Text style={[styles.emptyText, { color: colors.textSub }]}>Нет товаров</Text>
          ) : (
            <View style={styles.popularGrid}>
              {filteredPopular.map((item) => (
                <ProductCard key={item.id} product={mapProduct(item)} />
              ))}
            </View>
          )}
        </Section>

        <View style={{ height: 100 }} />
      </ScrollView>
    </ThemedView>
  );
}

function Section({
  title,
  children,
  hideAll,
}: {
  title: string;
  children: React.ReactNode;
  hideAll?: boolean;
}) {
  const colorScheme = useColorScheme() ?? 'light';
  const colors = Colors[colorScheme];

  return (
    <View style={styles.section}>
      <View style={styles.sectionHeader}>
        <Text style={[styles.sectionTitle, { color: colors.text }]}>{title}</Text>
        {!hideAll && (
          <TouchableOpacity>
            <Text style={[styles.seeAllText, { color: colors.primary }]}>Все</Text>
          </TouchableOpacity>
        )}
      </View>
      {children}
    </View>
  );
}

const styles = StyleSheet.create({
  container: {
    flex: 1,
  },
  searchSection: {
    marginBottom: 8,
  },
  section: {
    paddingHorizontal: 16,
    marginTop: 24,
  },
  sectionHeader: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    marginBottom: 16,
  },
  sectionTitle: {
    fontSize: 18,
    fontWeight: 'bold',
  },
  seeAllText: {
    fontSize: 14,
    fontWeight: '600',
  },
  productList: {
    gap: 16,
  },
  popularGrid: {
    flexDirection: 'row',
    flexWrap: 'wrap',
    gap: 16,
  },
  loader: {
    paddingVertical: 16,
  },
  emptyText: {
    fontSize: 14,
    textAlign: 'center',
    paddingVertical: 16,
  },
});
