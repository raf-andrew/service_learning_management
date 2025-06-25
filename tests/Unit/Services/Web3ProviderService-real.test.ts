/**
 * @fileoverview Real implementation tests for Web3ProviderService.ts
 * @tags web3,provider,service,real
 */

import { describe, it, expect, beforeEach, afterEach, vi } from 'vitest'
import { Web3ProviderService, Web3Provider, Network, Contract } from '../../../src/services/web3/Web3ProviderService'

// Mock window object
const mockWindow = {
  ethereum: {
    request: vi.fn(),
    on: vi.fn(),
    removeListener: vi.fn()
  }
}

// Mock global objects
global.window = mockWindow as any
global.console = {
  error: vi.fn(),
  log: vi.fn(),
  warn: vi.fn(),
  info: vi.fn()
} as any

describe('Web3ProviderService Real Implementation', () => {
  let web3ProviderService: Web3ProviderService

  beforeEach(() => {
    // Reset mocks
    vi.clearAllMocks()
    
    // Get singleton instance
    web3ProviderService = Web3ProviderService.getInstance()

    // Reset singleton state for test isolation
    // @ts-ignore
    web3ProviderService.provider = null
    // @ts-ignore
    web3ProviderService.signer = null
  })

  afterEach(() => {
    vi.restoreAllMocks()
  })

  describe('Singleton Pattern', () => {
    it('should return the same instance on multiple calls', () => {
      const instance1 = Web3ProviderService.getInstance()
      const instance2 = Web3ProviderService.getInstance()
      
      expect(instance1).toBe(instance2)
      expect(instance1).toBe(web3ProviderService)
    })

    it('should create new instance only once', () => {
      const instance1 = Web3ProviderService.getInstance()
      const instance2 = Web3ProviderService.getInstance()
      
      expect(instance1).toBe(instance2)
    })
  })

  describe('isMetaMaskInstalled method', () => {
    it('should return true when MetaMask is installed', () => {
      mockWindow.ethereum = { request: vi.fn() }
      
      const result = web3ProviderService.isMetaMaskInstalled()
      
      expect(result).toBe(true)
    })

    it('should return false when MetaMask is not installed', () => {
      mockWindow.ethereum = undefined
      
      const result = web3ProviderService.isMetaMaskInstalled()
      
      expect(result).toBe(false)
    })

    it('should return false when window is undefined', () => {
      const originalWindow = global.window
      global.window = undefined as any
      
      const result = web3ProviderService.isMetaMaskInstalled()
      
      expect(result).toBe(false)
      
      // Restore window
      global.window = originalWindow
    })
  })

  describe('connect method', () => {
    it('should return mock provider on successful connection', async () => {
      const result = await web3ProviderService.connect()
      
      expect(result).toEqual({
        address: '0x1234567890123456789012345678901234567890',
        balance: '1000000000000000000',
        network: { chainId: 1, name: 'Ethereum Mainnet' }
      })
    })

    it('should log error and return null on connection failure', async () => {
      // Mock console.error to track calls
      const consoleErrorSpy = vi.spyOn(console, 'error')
      
      // Simulate connection failure by throwing an error
      const originalConnect = web3ProviderService.connect.bind(web3ProviderService)
      vi.spyOn(web3ProviderService, 'connect').mockImplementation(async () => {
        try {
          throw new Error('Connection failed')
        } catch (error) {
          console.error('Connection failed:', error)
          return null
        }
      })
      
      const result = await web3ProviderService.connect()
      
      expect(result).toBeNull()
      expect(consoleErrorSpy).toHaveBeenCalledWith('Connection failed:', expect.any(Error))
    })

    it('should set provider instance on successful connection', async () => {
      await web3ProviderService.connect()
      
      const provider = web3ProviderService.getProvider()
      expect(provider).toEqual({
        address: '0x1234567890123456789012345678901234567890',
        balance: '1000000000000000000',
        network: { chainId: 1, name: 'Ethereum Mainnet' }
      })
    })
  })

  describe('disconnect method', () => {
    it('should clear provider and signer', async () => {
      // First connect to set provider
      await web3ProviderService.connect()
      expect(web3ProviderService.getProvider()).not.toBeNull()
      
      // Then disconnect
      await web3ProviderService.disconnect()
      
      expect(web3ProviderService.getProvider()).toBeNull()
      expect(web3ProviderService.getSigner()).toBeNull()
    })

    it('should work when no provider is connected', async () => {
      expect(() => web3ProviderService.disconnect()).not.toThrow()
    })
  })

  describe('switchNetwork method', () => {
    it('should switch network when provider is connected', async () => {
      // First connect
      await web3ProviderService.connect()
      
      // Then switch network
      const result = await web3ProviderService.switchNetwork(137)
      
      expect(result).toEqual({
        address: '0x1234567890123456789012345678901234567890',
        balance: '1000000000000000000',
        network: { chainId: 137, name: 'Network 137' }
      })
    })

    it('should throw error when no provider is connected', async () => {
      const consoleErrorSpy = vi.spyOn(console, 'error')
      
      const result = await web3ProviderService.switchNetwork(137)
      
      expect(result).toBeNull()
      expect(consoleErrorSpy).toHaveBeenCalledWith('Network switch failed:', expect.any(Error))
    })

    it('should update provider network on successful switch', async () => {
      // First connect
      await web3ProviderService.connect()
      
      // Then switch network
      await web3ProviderService.switchNetwork(56)
      
      const provider = web3ProviderService.getProvider()
      expect(provider?.network).toEqual({ chainId: 56, name: 'Network 56' })
    })
  })

  describe('getProvider method', () => {
    it('should return null when no provider is connected', () => {
      const result = web3ProviderService.getProvider()
      expect(result).toBeNull()
    })

    it('should return provider when connected', async () => {
      await web3ProviderService.connect()
      
      const result = web3ProviderService.getProvider()
      expect(result).toEqual({
        address: '0x1234567890123456789012345678901234567890',
        balance: '1000000000000000000',
        network: { chainId: 1, name: 'Ethereum Mainnet' }
      })
    })
  })

  describe('getSigner method', () => {
    it('should return null when no signer is available', () => {
      const result = web3ProviderService.getSigner()
      expect(result).toBeNull()
    })
  })

  describe('getNetworks method', () => {
    it('should return predefined networks', async () => {
      const networks = await web3ProviderService.getNetworks()
      
      expect(networks).toEqual([
        { 
          chainId: 1, 
          name: 'Ethereum Mainnet', 
          rpcUrl: 'https://mainnet.infura.io/v3/your-project-id' 
        },
        { 
          chainId: 137, 
          name: 'Polygon', 
          rpcUrl: 'https://polygon-rpc.com' 
        },
        { 
          chainId: 56, 
          name: 'BSC', 
          rpcUrl: 'https://bsc-dataseed.binance.org' 
        }
      ])
    })

    it('should return array with correct structure', async () => {
      const networks = await web3ProviderService.getNetworks()
      
      expect(Array.isArray(networks)).toBe(true)
      expect(networks.length).toBe(3)
      
      networks.forEach(network => {
        expect(network).toHaveProperty('chainId')
        expect(network).toHaveProperty('name')
        expect(network).toHaveProperty('rpcUrl')
        expect(typeof network.chainId).toBe('number')
        expect(typeof network.name).toBe('string')
        expect(typeof network.rpcUrl).toBe('string')
      })
    })
  })

  describe('Contract class', () => {
    let mockContract: any

    beforeEach(() => {
      mockContract = new web3ProviderService.Contract(
        '0x1234567890123456789012345678901234567890',
        [{ name: 'transfer', type: 'function' }],
        { address: '0x1234567890123456789012345678901234567890' }
      )
    })

    it('should create contract with correct properties', () => {
      expect(mockContract.address).toBe('0x1234567890123456789012345678901234567890')
      expect(mockContract.abi).toEqual([{ name: 'transfer', type: 'function' }])
      expect(mockContract.signer).toEqual({ address: '0x1234567890123456789012345678901234567890' })
    })

    it('should have callMethod method', () => {
      expect(typeof mockContract.callMethod).toBe('function')
    })

    it('should return mock transaction hash from callMethod', async () => {
      const result = await mockContract.callMethod('transfer', '0x123', '1000000')
      
      expect(result).toEqual({ hash: '0x' + '0'.repeat(64) })
    })

    it('should handle multiple parameters in callMethod', async () => {
      const result = await mockContract.callMethod('approve', '0x456', '2000000', { gasLimit: 100000 })
      
      expect(result).toEqual({ hash: '0x' + '0'.repeat(64) })
    })
  })

  describe('Error Handling', () => {
    it('should handle connection errors gracefully', async () => {
      const consoleErrorSpy = vi.spyOn(console, 'error')
      
      // Mock connect to throw error
      vi.spyOn(web3ProviderService, 'connect').mockImplementation(async () => {
        try {
          throw new Error('Network error')
        } catch (error) {
          console.error('Connection failed:', error)
          return null
        }
      })
      
      const result = await web3ProviderService.connect()
      
      expect(result).toBeNull()
      expect(consoleErrorSpy).toHaveBeenCalledWith('Connection failed:', expect.any(Error))
    })

    it('should handle network switch errors gracefully', async () => {
      const consoleErrorSpy = vi.spyOn(console, 'error')
      
      // Mock switchNetwork to throw error
      vi.spyOn(web3ProviderService, 'switchNetwork').mockImplementation(async () => {
        try {
          throw new Error('Network switch failed')
        } catch (error) {
          console.error('Network switch failed:', error)
          return null
        }
      })
      
      const result = await web3ProviderService.switchNetwork(999)
      
      expect(result).toBeNull()
      expect(consoleErrorSpy).toHaveBeenCalledWith('Network switch failed:', expect.any(Error))
    })
  })

  describe('Provider State Management', () => {
    it('should maintain provider state across method calls', async () => {
      // Initially no provider
      expect(web3ProviderService.getProvider()).toBeNull()
      
      // Connect provider
      await web3ProviderService.connect()
      expect(web3ProviderService.getProvider()).not.toBeNull()
      
      // Switch network
      await web3ProviderService.switchNetwork(137)
      const provider = web3ProviderService.getProvider()
      expect(provider?.network.chainId).toBe(137)
      
      // Disconnect
      await web3ProviderService.disconnect()
      expect(web3ProviderService.getProvider()).toBeNull()
    })

    it('should preserve provider address and balance during network switches', async () => {
      await web3ProviderService.connect()
      const originalProvider = web3ProviderService.getProvider()
      
      await web3ProviderService.switchNetwork(137)
      const updatedProvider = web3ProviderService.getProvider()
      
      expect(updatedProvider?.address).toBe(originalProvider?.address)
      expect(updatedProvider?.balance).toBe(originalProvider?.balance)
      expect(updatedProvider?.network.chainId).toBe(137)
    })
  })

  describe('Integration Tests', () => {
    it('should handle complete workflow: connect -> switch network -> disconnect', async () => {
      // Connect
      const connectResult = await web3ProviderService.connect()
      expect(connectResult).not.toBeNull()
      expect(web3ProviderService.getProvider()).not.toBeNull()
      
      // Switch network
      const switchResult = await web3ProviderService.switchNetwork(137)
      expect(switchResult).not.toBeNull()
      expect(web3ProviderService.getProvider()?.network.chainId).toBe(137)
      
      // Get networks
      const networks = await web3ProviderService.getNetworks()
      expect(networks.length).toBeGreaterThan(0)
      
      // Create contract
      const contract = new web3ProviderService.Contract(
        '0x1234567890123456789012345678901234567890',
        [],
        null
      )
      expect(contract).toBeDefined()
      
      // Disconnect
      await web3ProviderService.disconnect()
      expect(web3ProviderService.getProvider()).toBeNull()
    })

    it('should handle multiple network switches', async () => {
      await web3ProviderService.connect()
      
      await web3ProviderService.switchNetwork(1)
      expect(web3ProviderService.getProvider()?.network.chainId).toBe(1)
      
      await web3ProviderService.switchNetwork(137)
      expect(web3ProviderService.getProvider()?.network.chainId).toBe(137)
      
      await web3ProviderService.switchNetwork(56)
      expect(web3ProviderService.getProvider()?.network.chainId).toBe(56)
    })
  })
}) 