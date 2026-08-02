import React, { useState } from 'react';
import { View, Text, StyleSheet, TouchableOpacity, ActivityIndicator } from 'react-native';
import { IconSymbol } from '@/components/ui/icon-symbol';
import { Colors } from '@/constants/theme';
import { useColorScheme } from '@/hooks/use-color-scheme';
import { useRouter } from 'expo-router';
import { ApiReview } from '@/services/api';

// Deterministic avatar colour from customer id
const AVATAR_PALETTE = [
  { bg: '#dcfce7', text: '#15803d' },
  { bg: '#ffedd5', text: '#ea580c' },
  { bg: '#dbeafe', text: '#2563eb' },
  { bg: '#fce7f3', text: '#be185d' },
  { bg: '#ede9fe', text: '#7c3aed' },
];

function avatarColor(id: number) {
  return AVATAR_PALETTE[id % AVATAR_PALETTE.length];
}

function formatDate(iso: string): string {
  try {
    return new Date(iso).toLocaleDateString('ru-RU', {
      day: 'numeric',
      month: 'short',
      year: 'numeric',
    });
  } catch {
    return iso;
  }
}

function ReviewItem({ review, colors }: { review: ApiReview; colors: any }) {
  const [expanded, setExpanded] = useState(false);
  const isLong = review.comment.length > 100;
  const { bg, text } = avatarColor(review.customer.id);
  const initials = review.customer.name.charAt(0).toUpperCase();

  return (
    <View style={[styles.reviewCard, { backgroundColor: colors.surface, borderColor: colors.border }]}>
      <View style={styles.reviewHeader}>
        <View style={[styles.avatar, { backgroundColor: bg }]}>
          <Text style={[styles.avatarText, { color: text }]}>{initials}</Text>
        </View>
        <View style={styles.reviewerInfo}>
          <View style={styles.nameRow}>
            <Text style={[styles.reviewerName, { color: colors.text }]}>{review.customer.name}</Text>
            <Text style={styles.reviewDate}>{formatDate(review.created_at)}</Text>
          </View>
          <View style={styles.stars}>
            {[1, 2, 3, 4, 5].map((i) => (
              <IconSymbol
                key={i}
                name="star.fill"
                size={14}
                color={i <= review.rating ? '#facc15' : colors.border}
              />
            ))}
          </View>
        </View>
      </View>

      <Text
        style={[styles.reviewText, { color: colors.textSub }]}
        numberOfLines={expanded ? undefined : 2}
      >
        {review.comment}
      </Text>

      {isLong && (
        <TouchableOpacity
          style={styles.expandButton}
          onPress={() => setExpanded(!expanded)}
          activeOpacity={0.6}
        >
          <Text style={[styles.expandText, { color: colors.primary }]}>
            {expanded ? 'Свернуть' : 'Читать полностью'}
          </Text>
          <IconSymbol
            name={expanded ? 'chevron.up' : 'chevron.down'}
            size={14}
            color={colors.primary}
          />
        </TouchableOpacity>
      )}
    </View>
  );
}

interface ProductReviewsProps {
  productId: string;
  productName?: string;
  productImage?: string;
  reviews: ApiReview[];
  loading?: boolean;
  total?: number;
}

export function ProductReviews({
  productId,
  productName,
  productImage,
  reviews,
  loading,
  total = 0,
}: ProductReviewsProps) {
  const colorScheme = useColorScheme() ?? 'light';
  const colors = Colors[colorScheme];
  const router = useRouter();

  return (
    <View style={styles.container}>
      <View style={styles.header}>
        <Text style={[styles.title, { color: colors.text }]}>
          Отзывы{total > 0 ? ` (${total})` : ''}
        </Text>
        {total > reviews.length && (
          <TouchableOpacity
            onPress={() =>
              router.push({
                pathname: `/all-reviews/${productId}` as any,
                params: { id: productId, name: productName, image: productImage },
              })
            }
          >
            <Text style={[styles.seeAll, { color: colors.primary }]}>Все отзывы</Text>
          </TouchableOpacity>
        )}
      </View>

      {loading ? (
        <ActivityIndicator color={colors.primary} style={{ paddingVertical: 16 }} />
      ) : reviews.length === 0 ? (
        <View style={[styles.emptyBox, { backgroundColor: colors.surface, borderColor: colors.border }]}>
          <IconSymbol name="star" size={32} color={colors.border} />
          <Text style={[styles.emptyText, { color: colors.textSub }]}>
            Пока нет отзывов. Будьте первым!
          </Text>
        </View>
      ) : (
        <View style={styles.reviewsList}>
          {reviews.map((r) => (
            <ReviewItem key={r.id} review={r} colors={colors} />
          ))}
        </View>
      )}

      <TouchableOpacity
        style={[styles.writeReviewButton, { backgroundColor: colors.surface, borderColor: colors.border }]}
        onPress={() =>
          router.push({
            pathname: '/add-review',
            params: { id: productId, name: productName, image: productImage },
          })
        }
      >
        <IconSymbol name="square.and.pencil" size={20} color={colors.text} />
        <Text style={[styles.writeReviewText, { color: colors.text }]}>Написать отзыв</Text>
      </TouchableOpacity>
    </View>
  );
}

const styles = StyleSheet.create({
  container: {
    marginBottom: 24,
  },
  header: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    marginBottom: 16,
  },
  title: {
    fontSize: 18,
    fontWeight: '700',
  },
  seeAll: {
    fontSize: 14,
    fontWeight: '700',
  },
  reviewsList: {
    gap: 12,
    marginBottom: 16,
  },
  emptyBox: {
    alignItems: 'center',
    gap: 12,
    paddingVertical: 24,
    borderRadius: 16,
    borderWidth: 1,
    marginBottom: 16,
  },
  emptyText: {
    fontSize: 14,
    fontWeight: '500',
  },
  reviewCard: {
    padding: 16,
    borderRadius: 16,
    borderWidth: 1,
  },
  reviewHeader: {
    flexDirection: 'row',
    gap: 12,
    marginBottom: 12,
  },
  avatar: {
    width: 40,
    height: 40,
    borderRadius: 20,
    alignItems: 'center',
    justifyContent: 'center',
  },
  avatarText: {
    fontSize: 18,
    fontWeight: '700',
  },
  reviewerInfo: {
    flex: 1,
  },
  nameRow: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
  },
  reviewerName: {
    fontSize: 14,
    fontWeight: '700',
  },
  reviewDate: {
    fontSize: 12,
    color: '#9ca3af',
  },
  stars: {
    flexDirection: 'row',
    gap: 2,
    marginTop: 2,
  },
  reviewText: {
    fontSize: 14,
    lineHeight: 20,
  },
  expandButton: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 4,
    marginTop: 8,
    paddingVertical: 4,
  },
  expandText: {
    fontSize: 13,
    fontWeight: '700',
  },
  writeReviewButton: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'center',
    gap: 8,
    paddingVertical: 16,
    borderRadius: 12,
    borderWidth: 1,
  },
  writeReviewText: {
    fontSize: 14,
    fontWeight: '700',
  },
});
