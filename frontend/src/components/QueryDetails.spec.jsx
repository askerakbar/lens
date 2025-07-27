import { render, screen } from '@testing-library/react';
import QueryDetails from './QueryDetails';
import React from 'react';
import '@testing-library/jest-dom';

describe('QueryDetails', () => {
  const query = {
    id: 1,
    display_sql: 'SELECT * FROM users',
    connection_raw: { driver: 'mysql', database: 'testdb' },
    backtraces: [
      { file: '/path/to/file.js', line: 10, function: 'testFunc', class: 'TestClass' },
      { file: '/path/to/file2.js', line: 20, function: 'otherFunc', class: 'OtherClass' },
    ],
    query: 'SELECT * FROM users',
  };

  it('renders SQL section', () => {
    render(<QueryDetails query={query} />);
    expect(screen.getAllByText(/Query/i).length).toBeGreaterThan(0);
    // Use a function matcher to match the SQL string even if split across elements
    expect(screen.getByText((content, node) => {
      const hasText = (node) => node.textContent === 'SELECT * FROM users';
      const nodeHasText = hasText(node);
      const childrenDontHaveText = Array.from(node?.children || []).every(
        child => !hasText(child)
      );
      return nodeHasText && childrenDontHaveText;
    })).toBeInTheDocument();
  });

  it('renders Backtrace section', () => {
    render(<QueryDetails query={query} />);
    expect(screen.getByText(/Backtrace/i)).toBeInTheDocument();
    expect(screen.getByText(/testFunc\(\)/i)).toBeInTheDocument();
  });
}); 