-- Supabase Database Schema for CashControl
-- Run this in your Supabase SQL Editor: https://supabase.com/dashboard/project/kumslhaqyummcgytyvxv/sql

-- Create custom types
CREATE TYPE subscription_status AS ENUM ('active', 'cancelled', 'paused');
CREATE TYPE billing_cycle AS ENUM ('monthly', 'yearly', 'weekly', 'daily', 'one-time');
CREATE TYPE user_tier AS ENUM ('free', 'pro');

-- 1. User Profiles Table (extends Supabase auth.users)
CREATE TABLE public.user_profiles (
    id UUID REFERENCES auth.users(id) ON DELETE CASCADE PRIMARY KEY,
    email TEXT NOT NULL,
    full_name TEXT,
    user_tier user_tier DEFAULT 'free',
    has_paid BOOLEAN DEFAULT FALSE, -- Keep for backward compatibility
    payment_date TIMESTAMPTZ,
    stripe_customer_id TEXT,
    subscription_limit INTEGER DEFAULT 5, -- Free users get 5, Pro gets unlimited (-1)
    created_at TIMESTAMPTZ DEFAULT NOW(),
    updated_at TIMESTAMPTZ DEFAULT NOW()
);

-- 2. Subscriptions Table
CREATE TABLE public.subscriptions (
    id UUID DEFAULT gen_random_uuid() PRIMARY KEY,
    user_id UUID REFERENCES public.user_profiles(id) ON DELETE CASCADE NOT NULL,
    name TEXT NOT NULL,
    description TEXT,
    cost DECIMAL(10,2) NOT NULL,
    currency TEXT DEFAULT 'USD',
    billing_cycle billing_cycle NOT NULL,
    next_billing_date DATE,
    status subscription_status DEFAULT 'active',
    category TEXT,
    website_url TEXT,
    logo_url TEXT,
    notes TEXT,
    reminder_days INTEGER DEFAULT 3,
    created_at TIMESTAMPTZ DEFAULT NOW(),
    updated_at TIMESTAMPTZ DEFAULT NOW()
);

-- 3. Payment Records Table (for tracking Stripe payments)
CREATE TABLE public.payment_records (
    id UUID DEFAULT gen_random_uuid() PRIMARY KEY,
    user_id UUID REFERENCES public.user_profiles(id) ON DELETE CASCADE NOT NULL,
    stripe_payment_intent_id TEXT UNIQUE,
    stripe_session_id TEXT,
    amount INTEGER NOT NULL, -- in cents
    currency TEXT DEFAULT 'usd',
    status TEXT NOT NULL, -- succeeded, failed, pending
    created_at TIMESTAMPTZ DEFAULT NOW()
);

-- 4. Categories Table (predefined subscription categories)
CREATE TABLE public.categories (
    id UUID DEFAULT gen_random_uuid() PRIMARY KEY,
    name TEXT NOT NULL UNIQUE,
    icon TEXT,
    color TEXT,
    created_at TIMESTAMPTZ DEFAULT NOW()
);

-- Insert default categories
INSERT INTO public.categories (name, icon, color) VALUES
('Streaming', '📺', '#e74c3c'),
('Software', '💻', '#3498db'),
('Gaming', '🎮', '#9b59b6'),
('Music', '🎵', '#e67e22'),
('News', '📰', '#34495e'),
('Fitness', '💪', '#27ae60'),
('Education', '📚', '#f39c12'),
('Cloud Storage', '☁️', '#1abc9c'),
('Productivity', '⚡', '#2ecc71'),
('Other', '📦', '#95a5a6');

-- Enable Row Level Security (RLS)
ALTER TABLE public.user_profiles ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.subscriptions ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.payment_records ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.categories ENABLE ROW LEVEL SECURITY;

-- RLS Policies for user_profiles
CREATE POLICY "Users can view own profile" ON public.user_profiles
    FOR SELECT USING (auth.uid() = id);

CREATE POLICY "Users can update own profile" ON public.user_profiles
    FOR UPDATE USING (auth.uid() = id);

CREATE POLICY "Users can insert own profile" ON public.user_profiles
    FOR INSERT WITH CHECK (auth.uid() = id);

-- RLS Policies for subscriptions
CREATE POLICY "Users can view own subscriptions" ON public.subscriptions
    FOR SELECT USING (auth.uid() = user_id);

CREATE POLICY "Users can insert own subscriptions" ON public.subscriptions
    FOR INSERT WITH CHECK (auth.uid() = user_id);

CREATE POLICY "Users can update own subscriptions" ON public.subscriptions
    FOR UPDATE USING (auth.uid() = user_id);

CREATE POLICY "Users can delete own subscriptions" ON public.subscriptions
    FOR DELETE USING (auth.uid() = user_id);

-- RLS Policies for payment_records
CREATE POLICY "Users can view own payments" ON public.payment_records
    FOR SELECT USING (auth.uid() = user_id);

CREATE POLICY "Service role can insert payments" ON public.payment_records
    FOR INSERT WITH CHECK (true);

-- RLS Policies for categories (public read-only)
CREATE POLICY "Anyone can view categories" ON public.categories
    FOR SELECT USING (true);

-- Create indexes for better performance
CREATE INDEX idx_subscriptions_user_id ON public.subscriptions(user_id);
CREATE INDEX idx_subscriptions_status ON public.subscriptions(status);
CREATE INDEX idx_subscriptions_next_billing ON public.subscriptions(next_billing_date);
CREATE INDEX idx_payment_records_user_id ON public.payment_records(user_id);
CREATE INDEX idx_payment_records_stripe_id ON public.payment_records(stripe_payment_intent_id);

-- Create updated_at trigger function
CREATE OR REPLACE FUNCTION update_updated_at_column()
RETURNS TRIGGER AS $$
BEGIN
    NEW.updated_at = NOW();
    RETURN NEW;
END;
$$ language 'plpgsql';

-- Add updated_at triggers
CREATE TRIGGER update_user_profiles_updated_at BEFORE UPDATE ON public.user_profiles
    FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();

CREATE TRIGGER update_subscriptions_updated_at BEFORE UPDATE ON public.subscriptions
    FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();

-- Create a function to handle new user registration
CREATE OR REPLACE FUNCTION public.handle_new_user()
RETURNS TRIGGER AS $$
BEGIN
    INSERT INTO public.user_profiles (id, email, full_name)
    VALUES (NEW.id, NEW.email, NEW.raw_user_meta_data->>'full_name');
    RETURN NEW;
END;
$$ LANGUAGE plpgsql SECURITY DEFINER;

-- Trigger to automatically create profile when user signs up
CREATE TRIGGER on_auth_user_created
    AFTER INSERT ON auth.users
    FOR EACH ROW EXECUTE FUNCTION public.handle_new_user();

-- Grant necessary permissions
GRANT USAGE ON SCHEMA public TO anon, authenticated;
GRANT ALL ON ALL TABLES IN SCHEMA public TO anon, authenticated;
GRANT ALL ON ALL SEQUENCES IN SCHEMA public TO anon, authenticated;

-- Comments for documentation
COMMENT ON TABLE public.user_profiles IS 'Extended user information beyond Supabase auth';
COMMENT ON TABLE public.subscriptions IS 'User subscription tracking data';
COMMENT ON TABLE public.payment_records IS 'Stripe payment transaction records';
COMMENT ON TABLE public.categories IS 'Predefined subscription categories';

-- Success message
SELECT 'Database schema created successfully! 🎉' AS message;
