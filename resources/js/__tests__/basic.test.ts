import { describe, it, expect } from 'vitest';

describe('Basic Tests', () => {
  it('should pass basic arithmetic', () => {
    expect(2 + 2).toBe(4);
  });

  it('should handle string operations', () => {
    expect('Hello' + ' ' + 'World').toBe('Hello World');
  });

  it('should work with arrays', () => {
    const arr = [1, 2, 3];
    expect(arr.length).toBe(3);
    expect(arr[0]).toBe(1);
  });

  it('should work with objects', () => {
    const obj = { name: 'Test', value: 42 };
    expect(obj.name).toBe('Test');
    expect(obj.value).toBe(42);
  });
});
