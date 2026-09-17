import type { CodegenConfig } from '@graphql-codegen/cli';

import { DEFAULT_GRAPHQL_ENDPOINT } from './lib/defaults';

/**
 * Generates typed GraphQL operations from the live WPGraphQL schema and writes
 * a schema snapshot.
 *
 * Run against a booted environment:
 *   pnpm env:start
 *   pnpm codegen
 *
 * In CI the graphql-contract job runs this against a live WordPress. codegen
 * validates every query and fragment in queries.ts against the schema, so a
 * query that references a missing field fails the build.
 *
 * generated.ts and schema.graphql are git-ignored build artifacts. The
 * hand-authored types in lib/graphql/types.ts let the app type-check and build
 * without a live server, for example the WP-less js CI job.
 */
const config: CodegenConfig = {
  schema: process.env.NEXT_PUBLIC_GRAPHQL_ENDPOINT ?? DEFAULT_GRAPHQL_ENDPOINT,
  documents: ['lib/graphql/queries.ts'],
  ignoreNoDocuments: true,
  generates: {
    'lib/graphql/generated.ts': {
      plugins: ['typescript', 'typescript-operations', 'typed-document-node'],
      config: {
        useTypeImports: true,
        avoidOptionals: false,
      },
    },
    // A readable SDL snapshot of the schema the frontend was generated against.
    'schema.graphql': {
      plugins: ['schema-ast'],
    },
  },
};

export default config;
