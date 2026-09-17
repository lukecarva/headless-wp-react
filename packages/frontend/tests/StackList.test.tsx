import { render, screen } from '@testing-library/react';
import { describe, expect, it } from 'vitest';

import { StackList } from '@/components/StackList';

describe('StackList', () => {
  it('renders nothing when the stack is empty', () => {
    const { container } = render(<StackList stack={[]} />);
    expect(container).toBeEmptyDOMElement();
  });

  it('renders a labelled list with one item per technology', () => {
    render(<StackList stack={['React', 'WordPress', 'PHP']} />);

    const list = screen.getByRole('list', { name: 'Technology stack' });
    expect(list).toBeInTheDocument();
    expect(screen.getAllByRole('listitem')).toHaveLength(3);
    expect(screen.getByText('WordPress')).toBeInTheDocument();
  });
});
