import { render, screen, fireEvent } from '@testing-library/react';
import SearchInput from './SearchInput';
import { vi } from 'vitest';
import 'react';
import '@testing-library/jest-dom';

describe('SearchInput', () => {
  it('renders with placeholder', () => {
    render(<SearchInput placeholder="Type here..." value="" onChange={() => {}} onDebouncedChange={() => {}} />);
    expect(screen.getByPlaceholderText('Type here...')).toBeInTheDocument();
  });

  it('calls onChange when typing', () => {
    const handleChange = vi.fn();
    render(<SearchInput value="" onChange={handleChange} onDebouncedChange={() => {}} />);
    const input = screen.getByRole('textbox');
    fireEvent.change(input, { target: { value: 'abc' } });
    expect(handleChange).toHaveBeenCalled();
  });

  it('clears input when clear button is clicked', () => {
    const handleChange = vi.fn();
    render(<SearchInput value="abc" onChange={handleChange} onDebouncedChange={() => {}} />);
    const clearBtn = screen.getByRole('button', { name: /clear search/i });
    fireEvent.click(clearBtn);
    expect(handleChange).toHaveBeenCalled();
  });
}); 