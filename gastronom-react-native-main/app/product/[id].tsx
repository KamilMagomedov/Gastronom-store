import React, { useEffect, useRef, useState } from 'react';
import {
  ActivityIndicator,
  Dimensions,
  Image,
  NativeScrollEvent,
  NativeSyntheticEvent,
  ScrollView,
  StyleSheet,
  Text,
  TouchableOpacity,
  View,
} from 'react-native';
import { useLocalSearchParams, useRouter } from 'expo-router';
import { Colors } from '@/constants/theme';
import { useColorScheme } from '@/hooks/use-color-scheme';
import { ProductImageHeader } from '@/components/product/product-header';
import { ProductInfo } from '@/components/product/product-info';
import { ProductReviews } from '@/components/product/product-reviews';
import { ProductBottomBar } from '@/components/product/product-bottom-bar';
import { ThemedView } from '@/components/themed-view';
import { IconSymbol } from '@/components/ui/icon-symbol';
import { ApiService, ApiProductDetail, ApiReview } from '@/services/api';
import { ProductImagePlaceholder } from '@/components/ui/product-image-placeholder';

const { width: SCREEN_WIDTH, height: SCREEN_HEIGHT } = Dimensions.get('window');
const IMAGE_HEIGHT = SCREEN_HEIGHT * 0.45;

// ─── Image gallery with dots ────────────────────────────────────────────────
function ImageGallery({ images, fallback }: { images: string[]; fallback?: string }) {
  const [activeIndex, setActiveIndex] = useState(0);
  const colorScheme = useColorScheme() ?? 'light';
  const colors = Colors[colorScheme];

  const srcs = images.length > 0 ? images : fallback ? [fallback] : [];

  const onScroll = (e: NativeSyntheticEvent<NativeScrollEvent>) => {
    const index = Math.round(e.nativeEvent.contentOffset.x / SCREEN_WIDTH);
    setActiveIndex(index);
  };

  if (srcs.length === 0) {
    return (
      <View style={styles.imageContainer}>
        <ProductImagePlaceholder
          size="large"
          style={{ width: '100%', height: '100%', borderRadius: 0, borderWidth: 0, flex: undefined }}
        />
      </View>
    );
  }

  return (
    <View style={styles.imageContainer}>
      <ScrollView
        horizontal
        pagingEnabled
        showsHorizontalScrollIndicator={false}
        onScroll={onScroll}
        scrollEventThrottle={16}
      >
        {srcs.map((uri, i) => (
          <Image key={i} source={{ uri }} style={styles.image} resizeMode="cover" />
        ))}
      </ScrollView>

      {srcs.length > 1 && (
        <View style={styles.dotsRow}>
          {srcs.map((_, i) => (
            <View
              key={i}
              style={[
                styles.dot,
                i === activeIndex
                  ? { backgroundColor: '#13ec5b', width: 20 }
                  : { backgroundColor: 'rgba(255,255,255,0.6)' },
              ]}
            />
          ))}
        </View>
      )}

      <View style={styles.imageOverlay} />
    </View>
  );
}

// ─── Skeleton loader ─────────────────────────────────────────────────────────
function Skeleton({ colors }: { colors: any }) {
  return (
    <View style={styles.skeletonWrap}>
      <View style={[styles.skeletonBlock, { backgroundColor: colors.surface, height: 28, width: '70%' }]} />
      <View style={[styles.skeletonBlock, { backgroundColor: colors.surface, height: 20, width: '40%' }]} />
      <View style={{ height: 24 }} />
      <View style={[styles.skeletonBlock, { backgroundColor: colors.surface, height: 16, width: '100%' }]} />
      <View style={[styles.skeletonBlock, { backgroundColor: colors.surface, height: 16, width: '90%' }]} />
      <View style={[styles.skeletonBlock, { backgroundColor: colors.surface, height: 16, width: '80%' }]} />
    </View>
  );
}

// ─── Main screen ─────────────────────────────────────────────────────────────
export default function ProductScreen() {
  const colorScheme = useColorScheme() ?? 'light';
  const colors = Colors[colorScheme];
  // param name is "id" (from route [id]) but the value is the product slug
  const { id: slug } = useLocalSearchParams<{ id: string }>();
  const router = useRouter();

  const [product, setProduct] = useState<ApiProductDetail | null>(null);
  const [reviews, setReviews] = useState<ApiReview[]>([]);
  const [reviewsTotal, setReviewsTotal] = useState(0);
  const [loadingProduct, setLoadingProduct] = useState(true);
  const [loadingReviews, setLoadingReviews] = useState(true);
  const [error, setError] = useState(false);

  useEffect(() => {
    if (!slug) return;

    ApiService.getProduct(slug)
      .then((res) => setProduct(res.data))
      .catch(() => setError(true))
      .finally(() => setLoadingProduct(false));

    ApiService.getProductReviews(slug)
      .then((res) => {
        setReviews(res.data);
        setReviewsTotal(res.paginator.total);
      })
      .finally(() => setLoadingReviews(false));
  }, [slug]);

  // ── Error state ──────────────────────────────────────────────────────────
  if (error) {
    return (
      <ThemedView style={styles.centerFill}>
        <IconSymbol name="exclamationmark.circle" size={48} color={colors.textSub} />
        <Text style={[styles.errorText, { color: colors.textSub }]}>Не удалось загрузить товар</Text>
        <TouchableOpacity onPress={() => router.back()} style={[styles.retryBtn, { backgroundColor: colors.primary }]}>
          <Text style={styles.retryBtnText}>Назад</Text>
        </TouchableOpacity>
      </ThemedView>
    );
  }

  return (
    <ThemedView style={styles.container}>
      {/* Floating back/favourite buttons — always visible */}
      <ProductImageHeader image={product?.images?.[0] ?? ''} />

      <ScrollView showsVerticalScrollIndicator={false} contentContainerStyle={styles.scrollContent}>
        {/* ── Image gallery ─────────────────────────────────────────────── */}
        {loadingProduct ? (
          <View style={[styles.imageContainer, { backgroundColor: colors.surface }]}>
            <ActivityIndicator color={colors.primary} />
          </View>
        ) : (
          <ImageGallery images={product?.images ?? []} />
        )}

        {/* ── Info card ─────────────────────────────────────────────────── */}
        <View style={[styles.infoSection, { backgroundColor: colors.background }]}>
          <View style={[styles.dragHandle, { backgroundColor: colors.border }]} />

          {/* Breadcrumbs */}
          <View style={styles.breadcrumbs}>
            <TouchableOpacity onPress={() => router.push('/(tabs)')} style={styles.breadcrumbItem}>
              <Text style={[styles.breadcrumbText, { color: colors.textSub }]}>Главная</Text>
            </TouchableOpacity>
            <IconSymbol name="chevron.right" size={10} color={colors.textSub} style={styles.breadcrumbSep} />
            <Text
              style={[styles.breadcrumbText, { color: colors.text, fontWeight: '600' }]}
              numberOfLines={1}
            >
              {product?.name ?? '...'}
            </Text>
          </View>

          {/* SKU badge */}
          {product?.sku ? (
            <View style={[styles.skuBadge, { backgroundColor: colors.surface }]}>
              <Text style={[styles.skuText, { color: colors.textSub }]}>Арт: {product.sku}</Text>
            </View>
          ) : null}

          {/* Price / rating */}
          {loadingProduct ? (
            <Skeleton colors={colors} />
          ) : product ? (
            <ProductInfo
              name={product.name}
              price={parseFloat(product.price)}
              oldPrice={product.old_price ? parseFloat(product.old_price) : undefined}
              unit={product.unit}
              rating={product.rating.average}
              reviewsCount={product.rating.count}
            />
          ) : null}

          {/* Description */}
          {product?.description ? (
            <View style={styles.descriptionSection}>
              <Text style={[styles.sectionTitle, { color: colors.text }]}>Описание</Text>
              <Text style={[styles.description, { color: colors.textSub }]}>{product.description}</Text>
            </View>
          ) : null}

          {/* Reviews */}
          <ProductReviews
            productId={slug ?? ''}
            productName={product?.name}
            productImage={product?.images?.[0]}
            reviews={reviews}
            loading={loadingReviews}
            total={reviewsTotal}
          />

          <View style={{ height: 100 }} />
        </View>
      </ScrollView>

      <ProductBottomBar productId={product?.id} />
    </ThemedView>
  );
}

const styles = StyleSheet.create({
  container: { flex: 1 },
  centerFill: { flex: 1, alignItems: 'center', justifyContent: 'center', gap: 16, padding: 24 },
  scrollContent: { flexGrow: 1 },

  // Gallery
  imageContainer: {
    height: IMAGE_HEIGHT,
    width: '100%',
    backgroundColor: '#f3f4f6',
    alignItems: 'center',
    justifyContent: 'center',
    overflow: 'hidden',
  },
  image: {
    width: SCREEN_WIDTH,
    height: IMAGE_HEIGHT,
  },
  imageOverlay: {
    ...StyleSheet.absoluteFillObject,
    backgroundColor: 'rgba(0,0,0,0.04)',
    pointerEvents: 'none',
  },
  dotsRow: {
    position: 'absolute',
    bottom: 16,
    flexDirection: 'row',
    gap: 6,
    alignSelf: 'center',
  },
  dot: {
    height: 6,
    borderRadius: 3,
    width: 6,
  },

  // Info card
  infoSection: {
    flex: 1,
    marginTop: -32,
    borderTopLeftRadius: 32,
    borderTopRightRadius: 32,
    paddingHorizontal: 24,
    paddingTop: 12,
  },
  dragHandle: {
    width: 48,
    height: 6,
    borderRadius: 3,
    alignSelf: 'center',
    marginBottom: 16,
  },
  breadcrumbs: {
    flexDirection: 'row',
    flexWrap: 'wrap',
    alignItems: 'center',
    marginBottom: 12,
  },
  breadcrumbItem: { paddingVertical: 4 },
  breadcrumbSep: { marginHorizontal: 4 },
  breadcrumbText: { fontSize: 12 },

  skuBadge: {
    alignSelf: 'flex-start',
    paddingHorizontal: 10,
    paddingVertical: 4,
    borderRadius: 8,
    marginBottom: 12,
  },
  skuText: { fontSize: 12, fontWeight: '500' },

  // Description
  descriptionSection: { marginBottom: 24 },
  sectionTitle: { fontSize: 18, fontWeight: '700', marginBottom: 12 },
  description: { fontSize: 15, lineHeight: 22 },

  // Skeleton
  skeletonWrap: { gap: 10, marginBottom: 24 },
  skeletonBlock: { borderRadius: 8 },

  // Error
  errorText: { fontSize: 16, fontWeight: '500', textAlign: 'center' },
  retryBtn: { paddingHorizontal: 32, paddingVertical: 12, borderRadius: 16 },
  retryBtnText: { color: '#0d3b1d', fontWeight: '700', fontSize: 16 },
});
