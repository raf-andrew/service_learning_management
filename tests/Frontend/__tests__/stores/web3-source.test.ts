/**
 * @file web3-source.test.ts
 * @description Tests for the actual web3.ts Pinia store
 * @tags web3, source, frontend, store, vitest, pinia, async, ethereum, mocks
 */

import { describe, it, expect, beforeEach, vi, afterEach, beforeAll } from 'vitest'
import { setActivePinia, createPinia } from 'pinia'
import { ref } from 'vue'

// Mock external dependencies
const mockWeb3ProviderService = {
  isMetaMaskInstalled: vi.fn(),
  connect: vi.fn(),
  disconnect: vi.fn(),
  switchNetwork: vi.fn(),
  getProvider: vi.fn(),
  getSigner: vi.fn(),
  getNetworks: vi.fn(),
  Contract: vi.fn()
}
const mockWeb3Api = {
  getTransactionHistory: vi.fn(),
  getTransactionDetails: vi.fn(),
  getNetworkStatus: vi.fn(),
  getGasPriceEstimates: vi.fn()
}

vi.mock('../../../../src/services/web3/Web3ProviderService', () => ({
  web3ProviderService: mockWeb3ProviderService
}))
vi.mock('../../../../src/services/web3/Web3Api', () => ({
  web3Api: mockWeb3Api
}))

let useWeb3Store: any

describe('web3.ts Pinia Store (source test)', () => {
  let store: ReturnType<typeof useWeb3Store>

  beforeAll(async () => {
    const mod = await import('../../../../src/stores/web3')
    useWeb3Store = mod.useWeb3Store
  })

  beforeEach(() => {
    setActivePinia(createPinia())
    store = useWeb3Store()
    // Reset all mocks
    Object.values(mockWeb3ProviderService).forEach(fn => typeof fn === 'function' && fn.mockReset())
    Object.values(mockWeb3Api).forEach(fn => typeof fn === 'function' && fn.mockReset())
  })

  afterEach(() => {
    store.reset && store.reset()
  })

  describe('State and Getters', () => {
    it('should have default state', () => {
      expect(store.provider).toBeNull()
      expect(store.networks).toEqual([])
      expect(store.currentContract).toBeNull()
      expect(store.transactions).toEqual([])
      expect(store.gasPriceEstimates).toBeNull()
      expect(store.error).toBeNull()
      expect(store.isConnected).toBe(false)
      expect(store.currentAddress).toBeNull()
      expect(store.currentBalance).toBe('0')
      expect(store.currentNetwork).toBeNull()
    })
  })

  describe('initialize', () => {
    it('should throw if MetaMask is not installed', async () => {
      mockWeb3ProviderService.isMetaMaskInstalled.mockReturnValue(false)
      await expect(store.initialize()).rejects.toThrow('MetaMask is not installed')
      expect(store.error).toBe('MetaMask is not installed')
    })
    it('should throw if connect fails', async () => {
      mockWeb3ProviderService.isMetaMaskInstalled.mockReturnValue(true)
      mockWeb3ProviderService.connect.mockResolvedValue(null)
      await expect(store.initialize()).rejects.toThrow('Connection failed')
      expect(store.error).toBe('Connection failed')
    })
    it('should update state and fetch gas prices on success', async () => {
      mockWeb3ProviderService.isMetaMaskInstalled.mockReturnValue(true)
      const fakeProvider = { address: '0x123', balance: '1', network: 'mainnet' }
      mockWeb3ProviderService.connect.mockResolvedValue(fakeProvider)
      mockWeb3ProviderService.getNetworks.mockResolvedValue(['mainnet'])
      mockWeb3Api.getGasPriceEstimates.mockResolvedValue({ fast: 100 })
      await store.initialize()
      expect(store.provider).toEqual(fakeProvider)
      expect(store.networks).toEqual(['mainnet'])
      expect(store.gasPriceEstimates).toEqual({ fast: 100 })
      expect(store.error).toBeNull()
    })
  })

  describe('connect', () => {
    it('should throw if connect fails', async () => {
      mockWeb3ProviderService.connect.mockResolvedValue(null)
      await expect(store.connect()).rejects.toThrow('Connection failed')
      expect(store.error).toBe('Connection failed')
    })
    it('should update state and fetch gas prices on success', async () => {
      const fakeProvider = { address: '0xabc', balance: '2', network: 'rinkeby' }
      mockWeb3ProviderService.connect.mockResolvedValue(fakeProvider)
      mockWeb3ProviderService.getNetworks.mockResolvedValue(['rinkeby'])
      mockWeb3Api.getGasPriceEstimates.mockResolvedValue({ fast: 200 })
      await store.connect()
      expect(store.provider).toEqual(fakeProvider)
      expect(store.networks).toEqual(['rinkeby'])
      expect(store.gasPriceEstimates).toEqual({ fast: 200 })
      expect(store.error).toBeNull()
    })
  })

  describe('disconnect', () => {
    it('should call disconnect and reset state', async () => {
      mockWeb3ProviderService.disconnect.mockResolvedValue(undefined)
      store.provider = { address: '0x123', balance: '1', network: 'mainnet' }
      await store.disconnect()
      expect(store.provider).toBeNull()
      expect(store.networks).toEqual([])
      expect(store.currentContract).toBeNull()
      expect(store.transactions).toEqual([])
      expect(store.gasPriceEstimates).toBeNull()
      expect(store.error).toBeNull()
    })
    it('should set error if disconnect fails', async () => {
      mockWeb3ProviderService.disconnect.mockRejectedValue(new Error('fail'))
      await expect(store.disconnect()).rejects.toThrow('fail')
      expect(store.error).toBe('fail')
    })
  })

  describe('switchNetwork', () => {
    it('should throw if switchNetwork fails', async () => {
      mockWeb3ProviderService.switchNetwork.mockRejectedValue(new Error('fail'))
      await expect(store.switchNetwork(1)).rejects.toThrow('fail')
      expect(store.error).toBe('fail')
    })
    it('should update state and fetch gas prices on success', async () => {
      const fakeProvider = { address: '0xdef', balance: '3', network: 'goerli' }
      mockWeb3ProviderService.switchNetwork.mockResolvedValue(fakeProvider)
      mockWeb3ProviderService.getNetworks.mockResolvedValue(['goerli'])
      mockWeb3Api.getGasPriceEstimates.mockResolvedValue({ fast: 300 })
      await store.switchNetwork(5)
      expect(store.provider).toEqual(fakeProvider)
      expect(store.networks).toEqual(['goerli'])
      expect(store.gasPriceEstimates).toEqual({ fast: 300 })
      expect(store.error).toBeNull()
    })
  })

  describe('loadContract', () => {
    it('should throw if provider is not initialized', async () => {
      mockWeb3ProviderService.getProvider.mockReturnValue(null)
      await expect(store.loadContract('0x1', {})).rejects.toThrow('Web3 provider not initialized')
      expect(store.error).toBe('Web3 provider not initialized')
    })
    it('should set currentContract on success', async () => {
      mockWeb3ProviderService.getProvider.mockReturnValue({})
      const contract = await store.loadContract('0x2', { abi: [] })
      expect(store.currentContract).toEqual({ address: '0x2', abi: { abi: [] } })
      expect(contract).toEqual({ address: '0x2', abi: { abi: [] } })
      expect(store.error).toBeNull()
    })
  })

  describe('callContractMethod', () => {
    it('should throw if signer is not initialized', async () => {
      mockWeb3ProviderService.getSigner.mockReturnValue(null)
      await expect(store.callContractMethod('0x1', 'foo', [])).rejects.toThrow('Web3 signer not initialized')
      expect(store.error).toBe('Web3 signer not initialized')
    })
    it('should call contract method and return result', async () => {
      const fakeResult = 'result!'
      const fakeContract = { foo: vi.fn().mockResolvedValue(fakeResult) }
      mockWeb3ProviderService.getSigner.mockReturnValue('signer')
      mockWeb3ProviderService.Contract.mockImplementation(() => fakeContract)
      store.currentContract = { abi: [], address: '0x1' }
      const result = await store.callContractMethod('0x1', 'foo', [1, 2])
      expect(fakeContract.foo).toHaveBeenCalledWith(1, 2)
      expect(result).toBe(fakeResult)
      expect(store.error).toBeNull()
    })
    it('should set error if contract call fails', async () => {
      mockWeb3ProviderService.getSigner.mockReturnValue('signer')
      mockWeb3ProviderService.Contract.mockImplementation(() => ({ foo: vi.fn().mockRejectedValue(new Error('fail')) }))
      store.currentContract = { abi: [], address: '0x1' }
      await expect(store.callContractMethod('0x1', 'foo', [1])).rejects.toThrow('fail')
      expect(store.error).toBe('fail')
    })
  })

  describe('loadTransactions', () => {
    it('should throw if no address available', async () => {
      store.provider = { address: null }
      await expect(store.loadTransactions()).rejects.toThrow('No address available')
      expect(store.error).toBe('No address available')
    })
    it('should set transactions on first page', async () => {
      store.provider = { address: '0xabc' }
      mockWeb3Api.getTransactionHistory.mockResolvedValue([{ hash: '0x1' }])
      await store.loadTransactions(1)
      expect(store.transactions).toEqual([{ hash: '0x1' }])
      expect(store.error).toBeNull()
    })
    it('should append transactions on next page', async () => {
      store.provider = { address: '0xabc' }
      store.transactions = [{ hash: '0x1' }]
      mockWeb3Api.getTransactionHistory.mockResolvedValue([{ hash: '0x2' }])
      await store.loadTransactions(2)
      expect(store.transactions).toEqual([{ hash: '0x1' }, { hash: '0x2' }])
      expect(store.error).toBeNull()
    })
    it('should set error if loading fails', async () => {
      store.provider = { address: '0xabc' }
      mockWeb3Api.getTransactionHistory.mockRejectedValue(new Error('fail'))
      await expect(store.loadTransactions()).rejects.toThrow('fail')
      expect(store.error).toBe('fail')
    })
  })

  describe('getTransactionDetails', () => {
    it('should return transaction details', async () => {
      mockWeb3Api.getTransactionDetails.mockResolvedValue({ hash: '0x1' })
      const tx = await store.getTransactionDetails('0x1')
      expect(tx).toEqual({ hash: '0x1' })
      expect(store.error).toBeNull()
    })
    it('should set error if fails', async () => {
      mockWeb3Api.getTransactionDetails.mockRejectedValue(new Error('fail'))
      await expect(store.getTransactionDetails('0x1')).rejects.toThrow('fail')
      expect(store.error).toBe('fail')
    })
  })

  describe('getNetworkStatus', () => {
    it('should return network status', async () => {
      mockWeb3Api.getNetworkStatus.mockResolvedValue({ status: 'ok' })
      const status = await store.getNetworkStatus(1)
      expect(status).toEqual({ status: 'ok' })
      expect(store.error).toBeNull()
    })
    it('should set error if fails', async () => {
      mockWeb3Api.getNetworkStatus.mockRejectedValue(new Error('fail'))
      await expect(store.getNetworkStatus(1)).rejects.toThrow('fail')
      expect(store.error).toBe('fail')
    })
  })

  describe('fetchGasPrices', () => {
    it('should set gasPriceEstimates on success', async () => {
      mockWeb3Api.getGasPriceEstimates.mockResolvedValue({ fast: 123 })
      await store.fetchGasPrices()
      expect(store.gasPriceEstimates).toEqual({ fast: 123 })
      expect(store.error).toBeNull()
    })
    it('should set error if fails', async () => {
      mockWeb3Api.getGasPriceEstimates.mockRejectedValue(new Error('fail'))
      await expect(store.fetchGasPrices()).rejects.toThrow('fail')
      expect(store.error).toBe('fail')
    })
  })

  describe('clearError and reset', () => {
    it('should clear error', () => {
      store.error = 'fail'
      store.clearError()
      expect(store.error).toBeNull()
    })
    it('should reset all state', () => {
      store.provider = { address: '0x1' }
      store.networks = ['mainnet']
      store.currentContract = { address: '0x2', abi: [] }
      store.transactions = [{ hash: '0x1' }]
      store.gasPriceEstimates = { fast: 1 }
      store.error = 'fail'
      store.reset()
      expect(store.provider).toBeNull()
      expect(store.networks).toEqual([])
      expect(store.currentContract).toBeNull()
      expect(store.transactions).toEqual([])
      expect(store.gasPriceEstimates).toBeNull()
      expect(store.error).toBeNull()
    })
  })
}) 