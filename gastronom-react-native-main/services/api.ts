// const API_BASE_URL = 'https://gastronom-mob.adminstore.top/api';
const API_BASE_URL = 'http://127.0.0.1:8000/api';

export interface RegistrationData {
  name: string;
  email: string;
  password: string;
  password_confirmation: string;
}

export interface LoginData {
  email: string;
  password: string;
}

export interface AuthResponse {
  data: {
    token: string;
    customer: {
      id: number;
      name: string;
      email: string;
      email_verified?: boolean;
    };
  };
}

export interface ApiError {
  message: string;
  errors: {
    [key: string]: string[];
  };
}

export interface ApiCategory {
  id: number;
  name: string;
  slug: string;
}

export interface ApiProduct {
  id: number;
  name: string;
  slug: string;
  price: string;
  old_price: string | null;
  sku: string;
  unit: string;
  image: string;
  category: {
    id: number;
    name: string;
    slug: string;
  };
}

export interface ApiCartProduct {
  id: number;
  name: string;
  slug: string;
  price: string;
  old_price: string | null;
  sku: string;
  unit: string;
  images: string[];
}

export interface ApiCartItem {
  quantity: number;
  price: string;
  total: string;
  product: ApiCartProduct;
}

export interface ApiCart {
  total_amount: string;
  total_items: number;
  expires_at: string;
  session_id: string;
  items: ApiCartItem[];
}

export interface ApiProductDetail {
  id: number;
  name: string;
  slug: string;
  description: string;
  price: string;
  old_price: string | null;
  unit: string;
  sku: string;
  images: string[];
  rating: {
    average: number;
    count: number;
  };
}

export interface ApiReview {
  id: number;
  rating: number;
  comment: string;
  created_at: string;
  customer: {
    id: number;
    name: string;
  };
}

export interface ApiSearchResponse {
  data: ApiProduct[];
  paginator: {
    per_page: number;
    current_page: number;
    last_page: number;
    total: number;
    has_more: boolean;
  };
  success: boolean;
}

export interface ApiReviewsResponse {
  data: ApiReview[];
  paginator: {
    per_page: number;
    current_page: number;
    last_page: number;
    total: number;
    has_more: boolean;
  };
  success: boolean;
}

export interface DeliveryMethod {
  id: number;
  label: string;
  description: string;
  cost: number;
}

export interface PaymentMethod {
  id: number;
  name: string;
  description: string;
}

export interface OrderStatus {
  value: string;
  label: string;
}

export interface PaymentStatus {
  value: string;
  label: string;
}

export interface DeliverySettings {
  min_order_amount: string;
  free_delivery_threshold: string;
  delivery_fee: string;
}

export interface SocialLinks {
  facebook: string;
  instagram: string;
}

export interface AppSettings {
  app_name: string;
  app_version: string;
  currency: string;
  contact_email: string;
  contact_phone: string;
  social_links: SocialLinks;
  delivery_settings: DeliverySettings;
  delivery_methods: DeliveryMethod[];
  payment_methods: PaymentMethod[];
  order_statuses: OrderStatus[];
  payment_statuses: PaymentStatus[];
}

export interface CreateOrderData {
  delivery_method: number;
  payment_method: number;

  delivery_phone?: string;
  delivery_notes?: string;

  delivery_address?: string;

  delivery_street?: string;
  delivery_city?: string;
  delivery_apartment?: string;
  delivery_postal_code?: string;
  delivery_latitude?: number;
  delivery_longitude?: number;
  delivery_building?: string;
  delivery_entrance?: string;
  delivery_floor?: string;

  notes?: string;
}

export interface ApiOrderSummary {
  image: string;
  product_names: string;
  items_count: number;
  total_price: string;
}

export interface ApiOrderProduct {
  id: number;
  name: string;
  slug: string;
  price: string;
  old_price: string | null;
  sku: string;
  unit: string;
  image: string;

  category?: {
    id: number;
    name: string;
    slug: string;
    image?: string;
  };
}

export interface ApiOrder {
  id: number;
  total_amount: string;
  shipping_amount: string | null;
  delivery_cost: string;

  delivery_method: DeliveryMethod;

  payment_method: {
    id: number;
    label: string;
    description: string;
  };

  delivery_address?: string;
  delivery_phone?: string;

  payment_url?: string | null;

  status: string;
  payment_status: string;

  delivered_at?: string | null;
  created_at: string;

  products?: ApiOrderProduct[];
  order_summary?: ApiOrderSummary;
}

export interface ApiOrdersResponse {
  data: ApiOrder[];
  paginator: {
    per_page: number;
    current_page: number;
    last_page: number;
    total: number;
    has_more: boolean;
  };
  success: boolean;
}

async function request<T>(
  path: string,
  options: RequestInit = {},
): Promise<T> {
  const controller = new AbortController();
  const timeoutId = setTimeout(() => controller.abort(), 15000); // 15 second timeout

  try {
    console.log(`API: ${options.method || 'GET'} ${API_BASE_URL}${path}`);
    
    const response = await fetch(`${API_BASE_URL}${path}`, {
      ...options,
      signal: controller.signal,
      headers: {
        'Content-Type': 'application/json',
        Accept: 'application/json',
        ...(options.headers ?? {}),
      },
    });

    clearTimeout(timeoutId);
    console.log(`API: Response status: ${response.status} for ${path}`);

    const data = await response.json();

    if (!response.ok) {
      console.error(`API: Error response:`, data);
      throw data as ApiError;
    }

    return data as T;
  } catch (error) {
    clearTimeout(timeoutId);

    if (error instanceof Error) {
      if (error.name === 'AbortError') {
        console.error(`API: Request timeout for ${path}`);
        throw { message: 'Request timeout. Please check your connection.' };
      }
      console.error(`API: Network error for ${path}:`, error.message);
      throw { message: 'Network error. Please check your internet connection.' };
    }

    // Re-throw API error objects as-is (thrown by `throw data as ApiError` above).
    // Do NOT wrap them — callers need the original `errors` field for validation.
    throw error;
  }
}

export class ApiService {
  static async register(data: RegistrationData): Promise<AuthResponse> {
    return request<AuthResponse>('/v1/auth/register', {
      method: 'POST',
      body: JSON.stringify(data),
    });
  }

  static async login(data: LoginData): Promise<AuthResponse> {
    return request<AuthResponse>('/v1/auth/login', {
      method: 'POST',
      body: JSON.stringify(data),
    });
  }

  static async forgotPassword(email: string): Promise<{ message: string }> {
    return request<{ message: string }>('/v1/auth/forgot-password', {
      method: 'POST',
      body: JSON.stringify({ email }),
    });
  }

  static async resetPassword(
    token: string,
    email: string,
    password: string,
    password_confirmation: string,
  ): Promise<AuthResponse> {
    return request<AuthResponse>('/v1/auth/new-password', {
      method: 'POST',
      body: JSON.stringify({ token, email, password, password_confirmation }),
    });
  }

  static async getCategories(): Promise<{ data: ApiCategory[]; success: boolean }> {
    return request('/v1/home/categories');
  }

  static async getFrequentlyPurchased(): Promise<{ data: ApiProduct[]; success: boolean }> {
    return request('/v1/home/frequently-purchased');
  }

  static async getPopularProducts(): Promise<{ data: ApiProduct[]; success: boolean }> {
    return request('/v1/home/popular-products');
  }

  static async getSettings(): Promise<{ data: AppSettings; success: boolean }> {
    return request('/v1/settings');
  }

  static async getProduct(id: string): Promise<{ data: ApiProductDetail; success: boolean }> {
    return request(`/v1/products/${id}`);
  }

  static async getProductReviews(id: string): Promise<ApiReviewsResponse> {
    return request(`/v1/products/${id}/reviews`);
  }

  static async searchProducts(query: string, page = 1, limit = 20): Promise<ApiSearchResponse> {
    return request<ApiSearchResponse>(`/v1/search-products?page=${page}&limit=${limit}`, {
      method: 'POST',
      body: JSON.stringify({ query }),
    });
  }

  static async logout(token: string): Promise<void> {
    await request('/v1/auth/logout', {
      method: 'DELETE',
      headers: { Authorization: `Bearer ${token}` },
    });
  }

  static async getCart(token?: string, sessionId?: string): Promise<ApiCart> {
    const params = (!token && sessionId) ? `?session_id=${sessionId}` : '';
    const res = await request<{ data: ApiCart; success: boolean }>(`/v1/carts${params}`, {
      headers: token ? { Authorization: `Bearer ${token}` } : {},
    });
    return res.data;
  }

  static async addToCart(productId: number, quantity: number, token?: string, sessionId?: string): Promise<ApiCart> {
    const res = await request<{ data: ApiCart; success: boolean }>('/v1/carts', {
      method: 'POST',
      body: JSON.stringify({
        product_id: productId,
        quantity,
        ...(!token && sessionId ? { session_id: sessionId } : {}),
      }),
      headers: token ? { Authorization: `Bearer ${token}` } : {},
    });
    return res.data;
  }

  static async removeFromCart(productId: number, token?: string, sessionId?: string): Promise<ApiCart> {
    const params = (!token && sessionId) ? `?session_id=${sessionId}` : '';
    const res = await request<{ data: ApiCart; success: boolean }>(`/v1/carts/${productId}${params}`, {
      method: 'DELETE',
      headers: token ? { Authorization: `Bearer ${token}` } : {},
    });
    return res.data;
  }

  static async clearCart(token?: string, sessionId?: string): Promise<void> {
    const params = (!token && sessionId) ? `?session_id=${sessionId}` : '';
    
    await request(`/v1/carts-clear${params}`, {
      method: 'GET',
      headers: token ? { Authorization: `Bearer ${token}` } : {},
    });
  }

  static async createOrder(orderData: CreateOrderData, token?: string, sessionId?: string): Promise<{ data: ApiOrder; success: boolean }> {
    const params = (!token && sessionId) ? `?session_id=${sessionId}` : '';
    return request(`/v1/orders${params}`, {
      method: 'POST',
      body: JSON.stringify(orderData),
      headers: token ? { Authorization: `Bearer ${token}` } : {},
    });
  }

  static async getOrders(
    token: string,
    page = 1,
    perPage = 20,
  ): Promise<ApiOrdersResponse> {
    return request<ApiOrdersResponse>(
      `/v1/orders?page=${page}&per_page=${perPage}`,
      {
        headers: {
          Authorization: `Bearer ${token}`,
        },
      },
    );
  }
  
  static async getOrder(
    id: number | string,
    token: string,
  ): Promise<{ data: ApiOrder; success: boolean }> {
    return request<{ data: ApiOrder; success: boolean }>(
      `/v1/orders/${id}`,
      {
        headers: {
          Authorization: `Bearer ${token}`,
        },
      },
    );
  }

  static formatValidationErrors(error: ApiError): string[] {
    const errors: string[] = [];

    if (error.message) {
      errors.push(error.message);
    }

    if (error.errors) {
      Object.values(error.errors).forEach((fieldErrors) => {
        fieldErrors.forEach((msg) => errors.push(msg));
      });
    }

    return errors;
  }

  static getFieldErrors(error: ApiError): { [key: string]: string[] } {
    return error.errors ?? {};
  }
}
