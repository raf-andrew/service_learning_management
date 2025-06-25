import { vi } from 'vitest'

// Mock localStorage
const localStorageMock = {
  getItem: vi.fn(),
  setItem: vi.fn(),
  removeItem: vi.fn(),
  clear: vi.fn(),
}

Object.defineProperty(window, 'localStorage', {
  value: localStorageMock,
})

// Mock fetch
global.fetch = vi.fn()

// Mock Web3/ethers
vi.mock('ethers', () => ({
  ethers: {
    providers: {
      JsonRpcProvider: vi.fn(),
      Web3Provider: vi.fn(),
    },
    Contract: vi.fn(),
    Wallet: vi.fn(),
  },
}))

// Mock console methods to reduce noise in tests
global.console = {
  ...console,
  log: vi.fn(),
  debug: vi.fn(),
  info: vi.fn(),
  warn: vi.fn(),
  error: vi.fn(),
}

// Mock process.env
process.env.NODE_ENV = 'test'
process.env.VITE_API_URL = 'http://localhost:8000'
process.env.VITE_APP_NAME = 'Service Learning Management'

// Reset all mocks before each test
beforeEach(() => {
  vi.clearAllMocks()
  localStorageMock.getItem.mockReturnValue(null)
  localStorageMock.setItem.mockReturnValue(undefined)
  localStorageMock.removeItem.mockReturnValue(undefined)
  localStorageMock.clear.mockReturnValue(undefined)
}) 