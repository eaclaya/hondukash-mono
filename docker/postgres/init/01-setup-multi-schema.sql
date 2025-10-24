-- Multi-Schema Multi-Tenancy Setup
-- Creates a single database with schema-based isolation

-- Ensure we're in the correct database
\c hondukash_erp;

-- Create extensions if needed
CREATE EXTENSION IF NOT EXISTS "uuid-ossp";
CREATE EXTENSION IF NOT EXISTS "pg_trgm";

-- Set up the public schema for shared/system tables
-- This will contain tenant registry and shared functions

-- Grant necessary permissions
GRANT ALL ON SCHEMA public TO postgres;
GRANT USAGE ON SCHEMA public TO postgres;

-- Example tenant schemas (these will be created dynamically by the application)
-- But we can create a test tenant for development
-- CREATE SCHEMA IF NOT EXISTS tenant_001;
-- CREATE SCHEMA IF NOT EXISTS tenant_002;

-- The actual tenant schema creation will be handled by:
-- SELECT public.create_tenant_schema('tenant_001');

COMMENT ON DATABASE hondukash_erp IS 'Multi-tenant ERP system using schema-based isolation';
COMMENT ON SCHEMA public IS 'Shared schema containing tenant registry and system functions';