/**
 * @fileoverview Web3ProviderService - Mock implementation for testing
 * @tags web3,provider,service,mock
 */

export interface Web3Provider {
  address: string
  balance: string
  network: any
}

export interface Network {
  chainId: number
  name: string
  rpcUrl: string
}

export interface Contract {
  address: string
  abi: any
}

export class Web3ProviderService {
  private static instance: Web3ProviderService
  private provider: Web3Provider | null = null
  private signer: any = null
  private networks: Network[] = []

  static getInstance(): Web3ProviderService {
    if (!Web3ProviderService.instance) {
      Web3ProviderService.instance = new Web3ProviderService()
    }
    return Web3ProviderService.instance
  }

  isMetaMaskInstalled(): boolean {
    return typeof window !== 'undefined' && typeof (window as any).ethereum !== 'undefined'
  }

  async connect(): Promise<Web3Provider | null> {
    try {
      // Mock connection
      this.provider = {
        address: '0x1234567890123456789012345678901234567890',
        balance: '1000000000000000000',
        network: { chainId: 1, name: 'Ethereum Mainnet' }
      }
      return this.provider
    } catch (error) {
      console.error('Connection failed:', error)
      return null
    }
  }

  async disconnect(): Promise<void> {
    this.provider = null
    this.signer = null
  }

  async switchNetwork(chainId: number): Promise<Web3Provider | null> {
    try {
      if (!this.provider) {
        throw new Error('No provider connected')
      }

      this.provider.network = { chainId, name: `Network ${chainId}` }
      return this.provider
    } catch (error) {
      console.error('Network switch failed:', error)
      return null
    }
  }

  getProvider(): Web3Provider | null {
    return this.provider
  }

  getSigner(): any {
    return this.signer
  }

  async getNetworks(): Promise<Network[]> {
    return [
      { chainId: 1, name: 'Ethereum Mainnet', rpcUrl: 'https://mainnet.infura.io/v3/your-project-id' },
      { chainId: 137, name: 'Polygon', rpcUrl: 'https://polygon-rpc.com' },
      { chainId: 56, name: 'BSC', rpcUrl: 'https://bsc-dataseed.binance.org' }
    ]
  }

  Contract = class MockContract {
    address: string
    abi: any
    signer: any

    constructor(address: string, abi: any, signer: any) {
      this.address = address
      this.abi = abi
      this.signer = signer
    }

    async callMethod(methodName: string, ...params: any[]): Promise<any> {
      // Mock contract method call
      return { hash: '0x' + '0'.repeat(64) }
    }
  }
}

export const web3ProviderService = Web3ProviderService.getInstance() 