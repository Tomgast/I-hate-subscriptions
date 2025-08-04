-- Migration for CashControl user profiles table

-- Check if the user_profiles table exists
DO $$
BEGIN
    IF NOT EXISTS (SELECT FROM pg_tables WHERE schemaname = 'public' AND tablename = 'user_profiles') THEN
        -- Create user_profiles table if it doesn't exist
        CREATE TABLE public.user_profiles (
            id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
            email TEXT UNIQUE NOT NULL,
            full_name TEXT,
            avatar_url TEXT,
            has_paid BOOLEAN DEFAULT FALSE,
            payment_date TIMESTAMPTZ,
            created_at TIMESTAMPTZ DEFAULT NOW(),
            updated_at TIMESTAMPTZ DEFAULT NOW()
        );
        
        -- Add comment to the table
        COMMENT ON TABLE public.user_profiles IS 'User profiles for CashControl application';
    ELSE
        -- Check if has_paid column exists and add it if it doesn't
        IF NOT EXISTS (SELECT FROM pg_attribute WHERE attrelid = 'public.user_profiles'::regclass AND attname = 'has_paid') THEN
            ALTER TABLE public.user_profiles ADD COLUMN has_paid BOOLEAN DEFAULT FALSE;
        END IF;
        
        -- Check if payment_date column exists and add it if it doesn't
        IF NOT EXISTS (SELECT FROM pg_attribute WHERE attrelid = 'public.user_profiles'::regclass AND attname = 'payment_date') THEN
            ALTER TABLE public.user_profiles ADD COLUMN payment_date TIMESTAMPTZ;
        END IF;
    END IF;
END
$$;
