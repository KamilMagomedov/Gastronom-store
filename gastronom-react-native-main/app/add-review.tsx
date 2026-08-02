import React, { useState } from 'react';
import {
  StyleSheet,
  View,
  Text,
  TouchableOpacity,
  TextInput,
  ScrollView,
  Image,
  KeyboardAvoidingView,
  Platform,
} from 'react-native';
import { useRouter, useLocalSearchParams } from 'expo-router';
import { IconSymbol } from '@/components/ui/icon-symbol';
import { Colors } from '@/constants/theme';
import { useColorScheme } from '@/hooks/use-color-scheme';
import { ThemedView } from '@/components/themed-view';

export default function AddReviewScreen() {
  const router = useRouter();
  const { id, name, image } = useLocalSearchParams<{ id: string, name: string, image: string }>();
  const colorScheme = useColorScheme() ?? 'light';
  const colors = Colors[colorScheme];

  const [rating, setRating] = useState(4);
  const [review, setReview] = useState('');

  const renderStars = () => {
    return (
      <View style={styles.starsContainer}>
        {[1, 2, 3, 4, 5].map((star) => (
          <TouchableOpacity
            key={star}
            onPress={() => setRating(star)}
            activeOpacity={0.7}
          >
            <IconSymbol
              name="star.fill"
              size={40}
              color={star <= rating ? colors.primary : colorScheme === 'dark' ? '#374151' : '#d1d5db'}
            />
          </TouchableOpacity>
        ))}
      </View>
    );
  };

  const getRatingText = () => {
    switch (rating) {
      case 1: return 'Плохо, 1 из 5';
      case 2: return 'Так себе, 2 из 5';
      case 3: return 'Нормально, 3 из 5';
      case 4: return 'Хорошо, 4 из 5';
      case 5: return 'Отлично, 5 из 5';
      default: return '';
    }
  };

  const handleSubmit = () => {
    // Here you would normally send the data to a server
    router.push({
      pathname: '/review-success',
      params: { productId: id }
    });
  };

  return (
    <ThemedView style={styles.container}>
      {/* Header */}
      <View style={[styles.header, { borderBottomColor: colors.border }]}>
        <TouchableOpacity 
          onPress={() => router.back()} 
          style={styles.backButton}
        >
          <IconSymbol name="chevron.left" size={24} color={colors.text} />
        </TouchableOpacity>
        <Text style={[styles.headerTitle, { color: colors.text }]}>Добавление отзыва</Text>
        <View style={styles.headerRight} />
      </View>

      <KeyboardAvoidingView 
        behavior={Platform.OS === 'ios' ? 'padding' : 'height'}
        style={{ flex: 1 }}
      >
        <ScrollView 
          contentContainerStyle={styles.scrollContent}
          showsVerticalScrollIndicator={false}
        >
          {/* Product Card */}
          <View style={[styles.productCard, { backgroundColor: colors.surface, borderColor: colors.border }]}>
            <Image 
              source={{ uri: image || 'https://lh3.googleusercontent.com/aida-public/AB6AXuB23QBGzAjVCnWCAECYWFCX0C_5leorLbB1R70LDNLeO00y6btag1sFyvEZSCUefVxaB5XIc-rZQl85Ls2iYjLdopKHlAjMraJgrsBx73ZefC_PgOzEhF6DfAehPxYGuB6lkRVtoHLYNTeVu3nK5eHQV6vWPpBUjRmPRCiEC5vjORfNEXh6qBabItgleMqDwaAb-IkyQoQbRzbhhLW9p_S-X3yDQytbYTgd4GavS4pQA8zCBDoIWa48Gy5WPxbeEj7gLK1KE8PibkE' }} 
              style={styles.productImage} 
            />
            <View style={styles.productInfo}>
              <Text style={[styles.productName, { color: colors.text }]}>{name || 'Свежие томаты, 500г'}</Text>
              <Text style={[styles.productCategory, { color: colors.textSub }]}>Овощи и фрукты</Text>
            </View>
          </View>

          {/* Rating Section */}
          <View style={[styles.ratingSection, { backgroundColor: colors.surface, borderColor: colors.border }]}>
            <Text style={[styles.sectionTitle, { color: colors.text }]}>Ваша оценка</Text>
            {renderStars()}
            <Text style={[styles.ratingText, { color: colors.textSub }]}>{getRatingText()}</Text>
          </View>

          {/* Review Text Input */}
          <View style={styles.inputContainer}>
            <Text style={[styles.label, { color: colors.text }]}>Текст отзыва</Text>
            <TextInput
              style={[styles.textArea, { 
                backgroundColor: colors.surface, 
                borderColor: colors.border,
                color: colors.text 
              }]}
              placeholder="Расскажите, что вам понравилось, а что можно улучшить..."
              placeholderTextColor={colorScheme === 'dark' ? '#6b7280' : '#9ca3af'}
              multiline
              numberOfLines={6}
              textAlignVertical="top"
              value={review}
              onChangeText={setReview}
            />
          </View>

          {/* Photo Upload */}
          <TouchableOpacity 
            style={[styles.photoUpload, { borderColor: colors.primary + '66' }]}
            activeOpacity={0.7}
          >
            <View style={[styles.photoIconContainer, { backgroundColor: colors.primary + '33' }]}>
              <IconSymbol name="paperplane.fill" size={24} color={colors.primary} />
            </View>
            <Text style={[styles.photoUploadText, { color: colors.textSub }]}>Добавить фото товара</Text>
          </TouchableOpacity>
        </ScrollView>

        {/* Footer Button */}
        <View style={[styles.footer, { backgroundColor: colors.background, borderTopColor: colors.border }]}>
          <TouchableOpacity 
            style={[styles.submitButton, { backgroundColor: colors.primary }]}
            onPress={handleSubmit}
          >
            <Text style={styles.submitButtonText}>Отправить отзыв</Text>
            <IconSymbol name="paperplane.fill" size={20} color="#111813" />
          </TouchableOpacity>
        </View>
      </KeyboardAvoidingView>
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
    paddingTop: 60,
    paddingBottom: 16,
    borderBottomWidth: 1,
  },
  backButton: {
    padding: 8,
    marginLeft: -8,
  },
  headerTitle: {
    fontSize: 18,
    fontWeight: '700',
  },
  headerRight: {
    width: 40,
  },
  scrollContent: {
    padding: 20,
    gap: 24,
    paddingBottom: 120,
  },
  productCard: {
    flexDirection: 'row',
    padding: 12,
    borderRadius: 16,
    borderWidth: 1,
    alignItems: 'center',
    gap: 16,
  },
  productImage: {
    width: 64,
    height: 64,
    borderRadius: 12,
    backgroundColor: '#f3f4f6',
  },
  productInfo: {
    flex: 1,
  },
  productName: {
    fontSize: 16,
    fontWeight: '700',
    marginBottom: 4,
  },
  productCategory: {
    fontSize: 14,
    fontWeight: '500',
  },
  ratingSection: {
    paddingVertical: 24,
    borderRadius: 24,
    borderWidth: 1,
    alignItems: 'center',
    justifyContent: 'center',
  },
  sectionTitle: {
    fontSize: 18,
    fontWeight: '700',
    marginBottom: 16,
  },
  starsContainer: {
    flexDirection: 'row',
    gap: 8,
    marginBottom: 12,
  },
  ratingText: {
    fontSize: 14,
    fontWeight: '500',
  },
  inputContainer: {
    gap: 8,
  },
  label: {
    fontSize: 16,
    fontWeight: '700',
    paddingLeft: 4,
  },
  textArea: {
    width: '100%',
    minHeight: 160,
    borderRadius: 20,
    borderWidth: 1,
    padding: 16,
    fontSize: 16,
  },
  photoUpload: {
    width: '100%',
    borderWidth: 2,
    borderStyle: 'dashed',
    borderRadius: 20,
    padding: 16,
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'center',
    gap: 12,
  },
  photoIconContainer: {
    width: 44,
    height: 44,
    borderRadius: 22,
    alignItems: 'center',
    justifyContent: 'center',
  },
  photoUploadText: {
    fontSize: 16,
    fontWeight: '600',
  },
  footer: {
    position: 'absolute',
    bottom: 0,
    left: 0,
    right: 0,
    padding: 20,
    paddingBottom: Platform.OS === 'ios' ? 40 : 20,
    borderTopWidth: 1,
  },
  submitButton: {
    height: 56,
    borderRadius: 16,
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'center',
    gap: 8,
    shadowColor: '#13ec5b',
    shadowOffset: { width: 0, height: 4 },
    shadowOpacity: 0.3,
    shadowRadius: 14,
    elevation: 8,
  },
  submitButtonText: {
    fontSize: 18,
    fontWeight: '700',
    color: '#111813',
  },
});
