import React, { useState, useMemo } from 'react';
import { StyleSheet, View, Text, ScrollView, TouchableOpacity, Image } from 'react-native';
import { useLocalSearchParams, useRouter } from 'expo-router';
import { Colors } from '@/constants/theme';
import { useColorScheme } from '@/hooks/use-color-scheme';
import { IconSymbol } from '@/components/ui/icon-symbol';
import { ThemedView } from '@/components/themed-view';
import { SafeAreaView } from 'react-native-safe-area-context';

const REVIEWS = [
  {
    id: '1',
    user: 'Елена К.',
    rating: 5,
    date: '12 окт 2023',
    timestamp: 1697068800000,
    text: 'Помидоры просто супер! Очень сладкие и ароматные, как с грядки. Доставили быстро, упаковка не помята. Обязательно закажу еще для салата. Качество продукта действительно впечатляет, давно не встречала таких вкусных овощей в доставке.',
    helpful: 12,
    avatar: 'Е',
    avatarBg: '#dcfce7',
    avatarText: '#15803d',
    hasPhoto: true,
    images: ['https://lh3.googleusercontent.com/aida-public/AB6AXuDqmXfsF8kXPy-jKyGAq1lMYswEyqQFsHm70NifOYKnuLmAfrkT7eE35Egh0WqRgMpOZJWSVgERdsGbIuIdPWrbo66dzFV2vN41IBq9GyqKBDvz_qwslsqag66l_AGl4iGWLT8JhOFiuCB9MiYL7RnZp40FjITsuj69yws-p5sYuPZ5bJMKSl03lX7PdN2djCKe_F8jQu_v4BHDt0iEU68-F5nFJnoxpaKsayHWmXYh0FBWMe2H5IjIgkSj1smZ34RPIfc6RsZguGQ']
  },
  {
    id: '2',
    user: 'Дмитрий В.',
    rating: 2,
    date: '10 окт 2023',
    timestamp: 1696896000000,
    text: 'Хорошие томаты, но немного жестковата кожица. Для салата подошли отлично, но детям пришлось чистить. В целом доволен качеством за такую цену. Очень длинный комментарий, который точно не должен поместиться в две строки на мобильном устройстве. Мы проверяем функциональность развертывания текста, чтобы пользователь мог комфортно читать длинные отзывы только в том случае, если они ему интересны. Добавляем еще несколько предложений для гарантии срабатывания триггера развертывания.',
    helpful: 4,
    avatar: 'Д',
    avatarBg: '#ffedd5',
    avatarText: '#ea580c',
    hasPhoto: false
  },
  {
    id: '3',
    user: 'Светлана М.',
    rating: 5,
    date: '05 окт 2023',
    timestamp: 1696464000000,
    text: 'Всегда заказываю здесь овощи. Свежие, без гнили. Упакованы аккуратно в контейнер. Рекомендую!',
    helpful: 8,
    avatar: 'С',
    avatarBg: '#dbeafe',
    avatarText: '#2563eb',
    hasPhoto: true,
    images: ['https://lh3.googleusercontent.com/aida-public/AB6AXuA-3lt7ocn4HyNFDxGk3qqik81ih2twOj6A5LCn5_vYUAafqSWmNhr7igqvlwuq5bk860RhsPHGFPKs7RwhiYrdTXiwDCtC52N3lSulp3Ta4sqfWlCjfNJi5yAApCWlq0zS5g3FmLonQYbDIyv5trv3xwoLjBjkQNkeGGKMYb9hStLTndp4GSOPxGXHIb7-8wSG2cqBHSOiWqVHjkjKrrc-Z13CTUmzlN7U-0n6-pGkMxCSe457ejJZHlHrM502yJfAqdB1f9OBDcM']
  },
  {
    id: '4',
    user: 'Иван П.',
    rating: 5,
    date: 'Сегодня',
    timestamp: Date.now(),
    text: 'Отличное качество, очень свежие!',
    helpful: 0,
    avatar: 'И',
    avatarBg: '#f3e8ff',
    avatarText: '#7e22ce',
    hasPhoto: false
  }
];

const FILTERS = [
  { id: 'all', label: 'Все отзывы' },
  { id: 'photo', label: 'С фото' },
  { id: 'new', label: 'Сначала новые' },
  { id: 'negative', label: 'Отрицательные' }
];

function ReviewItem({ item, colors, colorScheme }: { item: typeof REVIEWS[0], colors: any, colorScheme: string }) {
  const [expanded, setExpanded] = useState(false);
  const isLongText = item.text.length > 100;

  return (
    <View key={item.id} style={[styles.reviewCard, { backgroundColor: colors.surface, borderColor: colors.border }]}>
      <View style={styles.reviewHeader}>
        <View style={[styles.avatar, { backgroundColor: item.avatarBg }]}>
          <Text style={[styles.avatarText, { color: item.avatarText }]}>{item.avatar}</Text>
        </View>
        <View style={styles.reviewerInfo}>
          <View style={styles.nameRow}>
            <Text style={[styles.reviewerName, { color: colors.text }]}>{item.user}</Text>
            <Text style={styles.reviewDate}>{item.date}</Text>
          </View>
          <View style={styles.stars}>
            {[1, 2, 3, 4, 5].map((star) => (
              <IconSymbol 
                key={star} 
                name="star.fill" 
                size={14} 
                color={star <= item.rating ? colors.primary : colors.border} 
              />
            ))}
          </View>
        </View>
      </View>
      
      <View>
        <Text 
          style={[styles.reviewText, { color: colors.textSub }]} 
          numberOfLines={expanded ? undefined : 2}
        >
          {item.text}
        </Text>
        
        {isLongText && (
          <TouchableOpacity 
            style={styles.expandButton} 
            onPress={() => setExpanded(!expanded)}
            activeOpacity={0.6}
          >
            <Text style={[styles.expandText, { color: colors.primary }]}>
              {expanded ? 'Свернуть' : 'Читать полностью'}
            </Text>
            <IconSymbol 
              name={expanded ? "chevron.up" : "chevron.down"} 
              size={14} 
              color={colors.primary} 
            />
          </TouchableOpacity>
        )}
      </View>
      
      {item.hasPhoto && item.images && (
        <ScrollView horizontal showsHorizontalScrollIndicator={false} style={styles.reviewImages}>
          {item.images.map((img, idx) => (
            <Image key={idx} source={{ uri: img }} style={styles.reviewImage} />
          ))}
        </ScrollView>
      )}

      <TouchableOpacity style={styles.helpfulButton}>
        <IconSymbol name="hand.thumbsup" size={16} color={colors.textSub} />
        <Text style={[styles.helpfulText, { color: colors.textSub }]}>Полезно ({item.helpful})</Text>
      </TouchableOpacity>
    </View>
  );
}

export default function AllReviewsScreen() {
  const { id, name, image } = useLocalSearchParams();
  const colorScheme = useColorScheme() ?? 'light';
  const colors = Colors[colorScheme];
  const router = useRouter();
  const [activeFilter, setActiveFilter] = useState('all');

  const filteredReviews = useMemo(() => {
    let result = [...REVIEWS];
    
    switch (activeFilter) {
      case 'photo':
        result = result.filter(r => r.hasPhoto);
        break;
      case 'new':
        result = result.sort((a, b) => b.timestamp - a.timestamp);
        break;
      case 'negative':
        result = result.filter(r => r.rating <= 3);
        break;
      default:
        break;
    }
    
    return result;
  }, [activeFilter]);

  return (
    <ThemedView style={styles.container}>
      <SafeAreaView edges={['top']} style={{ backgroundColor: colors.background }}>
        <View style={styles.header}>
          <TouchableOpacity onPress={() => router.back()} style={styles.backButton}>
            <IconSymbol name="chevron.left" size={24} color={colors.text} />
          </TouchableOpacity>
          <Text style={[styles.headerTitle, { color: colors.text }]}>Все отзывы о товаре</Text>
          <View style={{ width: 40 }} />
        </View>
      </SafeAreaView>

      <ScrollView contentContainerStyle={styles.scrollContent} showsVerticalScrollIndicator={false}>
        {/* Product Info Card */}
        <View style={[styles.productCard, { backgroundColor: colors.surface, borderColor: colors.border }]}>
          <Image source={{ uri: image as string }} style={styles.productImage} />
          <View>
            <Text style={[styles.productName, { color: colors.text }]}>{name}</Text>
            <Text style={styles.productCategory}>Овощи и фрукты</Text>
          </View>
        </View>

        {/* Rating Summary */}
        <View style={[styles.ratingSummary, { backgroundColor: colors.surface, borderColor: colors.border }]}>
          <View style={styles.ratingLeft}>
            <Text style={[styles.ratingValue, { color: colors.text }]}>4.8</Text>
            <View style={styles.starsLarge}>
              {[1, 2, 3, 4].map(i => <IconSymbol key={i} name="star.fill" size={18} color={colors.primary} />)}
              <IconSymbol name="star.leadinghalf.filled" size={18} color={colors.primary} />
            </View>
            <Text style={styles.ratingCount}>{REVIEWS.length} оценок</Text>
          </View>
          <View style={styles.ratingRight}>
            {[5, 4, 3, 2, 1].map((rating) => (
              <View key={rating} style={styles.ratingRow}>
                <Text style={styles.ratingRowLabel}>{rating}</Text>
                <View style={[styles.progressBar, { backgroundColor: colorScheme === 'dark' ? '#374151' : '#f3f4f6' }]}>
                  <View style={[styles.progressFill, { backgroundColor: colors.primary, width: `${rating === 5 ? 85 : rating === 4 ? 10 : 3}%` }]} />
                </View>
              </View>
            ))}
          </View>
        </View>

        {/* Filter Chips */}
        <ScrollView horizontal showsHorizontalScrollIndicator={false} contentContainerStyle={styles.filtersScroll}>
          {FILTERS.map(filter => (
            <TouchableOpacity 
              key={filter.id} 
              onPress={() => setActiveFilter(filter.id)}
              style={[
                styles.filterChip, 
                { 
                  backgroundColor: activeFilter === filter.id ? colors.primary : colors.surface,
                  borderColor: activeFilter === filter.id ? colors.primary : colors.border,
                  borderWidth: 1
                }
              ]}
            >
              <Text style={[
                styles.filterText, 
                { 
                  color: activeFilter === filter.id ? '#000' : colors.textSub, 
                  fontWeight: activeFilter === filter.id ? '700' : '500' 
                }
              ]}>
                {filter.label}
              </Text>
            </TouchableOpacity>
          ))}
        </ScrollView>

        {/* Reviews List */}
        <View style={styles.reviewsList}>
          {filteredReviews.length > 0 ? (
            filteredReviews.map(item => <ReviewItem key={item.id} item={item} colors={colors} colorScheme={colorScheme} />)
          ) : (
            <View style={styles.emptyContainer}>
              <Text style={{ color: colors.textSub }}>Отзывов не найдено</Text>
            </View>
          )}
        </View>

        {/* Pagination - Simple visibility control */}
        {filteredReviews.length > 3 && (
          <View style={styles.pagination}>
            <TouchableOpacity disabled style={[styles.pageButton, { borderColor: colors.border, opacity: 0.5 }]}>
              <IconSymbol name="chevron.left" size={20} color={colors.textSub} />
            </TouchableOpacity>
            <TouchableOpacity style={[styles.pageButton, { backgroundColor: colors.primary, borderColor: colors.primary }]}>
              <Text style={[styles.pageButtonText, { color: '#000' }]}>1</Text>
            </TouchableOpacity>
            <TouchableOpacity style={[styles.pageButton, { borderColor: colors.border }]}>
              <Text style={[styles.pageButtonText, { color: colors.text }]}>2</Text>
            </TouchableOpacity>
            <Text style={{ color: colors.textSub }}>...</Text>
            <TouchableOpacity style={[styles.pageButton, { borderColor: colors.border }]}>
              <IconSymbol name="chevron.right" size={20} color={colors.textSub} />
            </TouchableOpacity>
          </View>
        )}
      </ScrollView>
    </ThemedView>
  );
}

const styles = StyleSheet.create({
  container: {
    flex: 1,
  },
  header: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    paddingHorizontal: 16,
    paddingVertical: 12,
  },
  backButton: {
    padding: 8,
  },
  headerTitle: {
    fontSize: 18,
    fontWeight: '700',
  },
  scrollContent: {
    padding: 20,
    paddingBottom: 40,
  },
  productCard: {
    flexDirection: 'row',
    alignItems: 'center',
    padding: 12,
    borderRadius: 16,
    borderWidth: 1,
    marginBottom: 20,
    gap: 16,
  },
  productImage: {
    width: 56,
    height: 56,
    borderRadius: 12,
  },
  productName: {
    fontSize: 16,
    fontWeight: '700',
  },
  productCategory: {
    fontSize: 14,
    color: '#61896f',
    marginTop: 2,
  },
  ratingSummary: {
    flexDirection: 'row',
    padding: 20,
    borderRadius: 20,
    borderWidth: 1,
    marginBottom: 20,
    gap: 24,
  },
  ratingLeft: {
    alignItems: 'center',
    justifyContent: 'center',
  },
  ratingValue: {
    fontSize: 48,
    fontWeight: '800',
    letterSpacing: -1,
  },
  starsLarge: {
    flexDirection: 'row',
    gap: 2,
    marginVertical: 4,
  },
  ratingCount: {
    fontSize: 12,
    color: '#61896f',
    fontWeight: '600',
  },
  ratingRight: {
    flex: 1,
    gap: 6,
    justifyContent: 'center',
  },
  ratingRow: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 12,
  },
  ratingRowLabel: {
    fontSize: 12,
    fontWeight: '700',
    color: '#61896f',
    width: 8,
  },
  progressBar: {
    flex: 1,
    height: 8,
    borderRadius: 4,
    overflow: 'hidden',
  },
  progressFill: {
    height: '100%',
    borderRadius: 4,
  },
  filtersScroll: {
    gap: 8,
    marginBottom: 24,
  },
  filterChip: {
    paddingHorizontal: 16,
    paddingVertical: 8,
    borderRadius: 20,
  },
  filterText: {
    fontSize: 14,
  },
  reviewsList: {
    gap: 16,
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
    marginBottom: 8,
  },
  expandButton: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 4,
    marginBottom: 12,
    paddingVertical: 4,
  },
  expandText: {
    fontSize: 13,
    fontWeight: '700',
  },
  helpfulButton: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 4,
  },
  helpfulText: {
    fontSize: 12,
    fontWeight: '600',
  },
  pagination: {
    flexDirection: 'row',
    justifyContent: 'center',
    alignItems: 'center',
    gap: 8,
    marginTop: 24,
  },
  pageButton: {
    width: 40,
    height: 40,
    borderRadius: 12,
    borderWidth: 1,
    alignItems: 'center',
    justifyContent: 'center',
  },
  pageButtonText: {
    fontSize: 14,
    fontWeight: '600',
  },
  emptyContainer: {
    padding: 40,
    alignItems: 'center',
  },
  reviewImages: {
    flexDirection: 'row',
    marginBottom: 12,
  },
  reviewImage: {
    width: 80,
    height: 80,
    borderRadius: 8,
    marginRight: 8,
  }
});
