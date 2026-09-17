import { GraphQLClient, type RequestOptions, type Variables } from 'graphql-request';
import type { TypedDocumentNode } from '@graphql-typed-document-node/core';

import { env } from '@/lib/env';

/**
 * A single shared GraphQL client pointed at WPGraphQL.
 *
 * Requests use HTTP **GET** on purpose. Next's Data Cache only caches GET
 * fetches, so a default POST would make the `next.revalidate` / `tags` options
 * below inert. With GET, read queries are cached, revalidated on the configured
 * interval, and can be busted on demand via `revalidateTag('wpgraphql')` (see
 * `app/api/revalidate/route.ts`). WPGraphQL serves queries over GET; only
 * mutations require POST, and this frontend is read-only.
 */
const client = new GraphQLClient(env.graphqlEndpoint, {
  method: 'GET',
  fetch: (url, options) =>
    fetch(url, {
      ...options,
      next: { revalidate: env.revalidateSeconds, tags: ['wpgraphql'] },
    }),
});

/**
 * Execute a typed GraphQL document.
 *
 * Works with plain documents today and with `graphql-codegen`'s
 * `TypedDocumentNode` output once `pnpm codegen` has run against a live schema,
 * giving fully-inferred `TData`/`TVariables` at every call site.
 */
export async function gqlRequest<TData, TVariables extends Variables = Variables>(
  document: TypedDocumentNode<TData, TVariables> | string,
  ...[variables]: TVariables extends Record<string, never> ? [] : [TVariables]
): Promise<TData> {
  // Use the single-object request form. Its public type is a conditional that
  // does not accept a widened `{ document, variables }` literal, so we assert
  // the concrete shape we know is correct.
  const options = { document, variables } as unknown as RequestOptions<TVariables, TData>;
  return client.request<TData, TVariables>(options);
}
