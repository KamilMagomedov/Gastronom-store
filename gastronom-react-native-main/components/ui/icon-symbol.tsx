// Fallback for using MaterialIcons on Android and web.

import MaterialIcons from '@expo/vector-icons/MaterialIcons';
import { SymbolWeight, SymbolViewProps } from 'expo-symbols';
import { ComponentProps } from 'react';
import { OpaqueColorValue, type StyleProp, type TextStyle } from 'react-native';

type IconMapping = Record<string, ComponentProps<typeof MaterialIcons>['name']>;
type IconSymbolName = string;

/**
 * Add your SF Symbols to Material Icons mappings here.
 * - see Material Icons in the [Icons Directory](https://icons.expo.fyi).
 * - see SF Symbols in the [SF Symbols](https://developer.apple.com/sf-symbols/) app.
 */
const MAPPING = {
  // Navigation
  'house.fill': 'home',
  'paperplane.fill': 'send',
  'chevron.left.forwardslash.chevron.right': 'code',
  'chevron.right': 'chevron-right',
  'chevron.left': 'chevron-left',
  'chevron.down': 'keyboard-arrow-down',
  
  // Home & Search
  'bell': 'notifications',
  'magnifyingglass': 'search',
  'slider.horizontal.3': 'tune',
  'line.3.horizontal.decrease.circle': 'sort',
  'line.3.horizontal.decrease': 'filter-list',
  'heart': 'favorite-border',
  'plus': 'add',
  'minus': 'remove',
  'nutrition': 'restaurant',
  'cart': 'shopping-cart',
  'person': 'person',
  'search': 'search',
  'house': 'home',
  'heart.fill': 'favorite',
  'cart.fill': 'shopping-cart',
  'person.fill': 'person',
  'bell.fill': 'notifications',
  'star.fill': 'star',
  'star': 'star-border',
  'xmark': 'close',
  'square.and.pencil': 'rate-review',
  'receipt.description.fill': 'receipt-long',
  'clock.fill': 'history',
  'bag.fill': 'local-mall',
  'storefront.fill': 'storefront',
  'basket.fill': 'shopping-basket',
  'drop.fill': 'water-drop',
  'arrow.clockwise': 'replay',
  'info.circle.fill': 'info',
  'camera.fill': 'photo-camera',
  'envelope': 'mail',
  'phone': 'call',
  'mappin': 'location-on',
  'notifications': 'notifications',
  'tune': 'tune',
  'favorite': 'favorite',
} as IconMapping;

/**
 * An icon component that uses native SF Symbols on iOS, and Material Icons on Android and web.
 * This ensures a consistent look across platforms, and optimal resource usage.
 * Icon `name`s are based on SF Symbols and require manual mapping to Material Icons.
 */
export function IconSymbol({
  name,
  size = 24,
  color,
  style,
}: {
  name: string;
  size?: number;
  color: string | OpaqueColorValue;
  style?: StyleProp<TextStyle>;
  weight?: SymbolWeight;
}) {
  return <MaterialIcons color={color} size={size} name={MAPPING[name] || 'help-outline'} style={style} />;
}
