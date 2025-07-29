import { render, screen } from '@testing-library/react';
import QueryList from './QueryList';
import 'react';
import '@testing-library/jest-dom';

describe('QueryList', () => {
  it('shows empty state when no queries', () => {
    render(<QueryList queries={[]} lastQueryElementRef={null} />);
    expect(screen.getByText(/No queries here yet!/i)).toBeInTheDocument();
  });

  it('renders queries when provided', () => {
    const queries = [
      {
        id: 1,
        content: { sql: 'SELECT 1', duration: 0.05, timestamp: new Date().toISOString(), trace: [] },
        display_sql: 'SELECT 1',
      },
    ];
    render(<QueryList queries={queries} lastQueryElementRef={null} />);
    expect(screen.getByText(/SELECT 1/i)).toBeInTheDocument();
  });
}); 