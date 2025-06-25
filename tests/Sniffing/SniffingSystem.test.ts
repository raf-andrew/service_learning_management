/**
 * @tag sniffing
 * @tag backend
 * @tag vitest
 * @tag integration
 * @related app/Console/Commands/SniffingCommand.php
 * @related app/Services/SniffingReportService.php
 * @related app/Models/SniffResult.php
 * @related app/Models/SniffViolation.php
 *
 * Tests the Sniffing System end-to-end, including initialization, analysis, report generation,
 * violation detection, naming conventions, documentation, and database storage. Ensures all
 * code analysis and reporting features are covered for backend sniffing infrastructure.
 */
import { describe, it, expect, beforeEach, afterEach } from 'vitest'
import { exec } from 'child_process'
import { promisify } from 'util'
import fs from 'fs'
import path from 'path'

const execAsync = promisify(exec)

describe('Sniffing System', () => {
  const testFile = 'test.php'
  const reportDir = '../../.sniffing/reports'

  beforeEach(async () => {
    // Create test PHP file
    const phpCode = `
<?php

namespace App\Services;

class TestService
{
    private $variable;

    public function __construct()
    {
        $this->variable = 'test';
    }

    public function process($data)
    {
        return $data;
    }
}
`
    fs.writeFileSync(testFile, phpCode)

    // Ensure report directory exists
    if (!fs.existsSync(reportDir)) {
      fs.mkdirSync(reportDir, { recursive: true })
    }
  })

  afterEach(() => {
    // Clean up test file
    if (fs.existsSync(testFile)) {
      fs.unlinkSync(testFile)
    }
  })

  it('should initialize sniffing system', async () => {
    const { stdout } = await execAsync('php artisan sniffing:init')
    expect(stdout).toContain('Sniffing system initialized successfully')
  })

  it('should run sniffing analysis', async () => {
    const { stdout } = await execAsync('php artisan sniffing:analyze --format=html --file=' + testFile)
    expect(stdout).toContain('Analysis completed successfully')
  })

  it('should generate HTML report', async () => {
    await execAsync('php artisan sniffing:analyze --format=html --file=' + testFile)
    const reports = fs.readdirSync(reportDir)
    const htmlReport = reports.find(file => file.endsWith('.html'))
    expect(htmlReport).toBeDefined()
  })

  it('should generate JSON report', async () => {
    await execAsync('php artisan sniffing:analyze --format=json --file=' + testFile)
    const reports = fs.readdirSync(reportDir)
    const jsonReport = reports.find(file => file.endsWith('.json'))
    expect(jsonReport).toBeDefined()
  })

  it('should detect coding standard violations', async () => {
    const { stdout } = await execAsync('php artisan sniffing:analyze --format=json --file=' + testFile)
    const reports = fs.readdirSync(reportDir)
    const jsonReport = reports.find(file => file.endsWith('.json'))
    const reportContent = JSON.parse(fs.readFileSync(path.join(reportDir, jsonReport!), 'utf-8'))
    expect(reportContent.violations).toBeDefined()
  })

  it('should validate class naming convention', async () => {
    const { stdout } = await execAsync('php artisan sniffing:analyze --format=json --file=' + testFile)
    const reports = fs.readdirSync(reportDir)
    const jsonReport = reports.find(file => file.endsWith('.json'))
    const reportContent = JSON.parse(fs.readFileSync(path.join(reportDir, jsonReport!), 'utf-8'))
    const violations = reportContent.violations
    const namingViolations = violations.filter((v: any) => v.source.includes('ServiceLearning.Classes.ClassDeclaration'))
    expect(namingViolations.length).toBeGreaterThan(0)
  })

  it('should validate method documentation', async () => {
    const { stdout } = await execAsync('php artisan sniffing:analyze --format=json --file=' + testFile)
    const reports = fs.readdirSync(reportDir)
    const jsonReport = reports.find(file => file.endsWith('.json'))
    const reportContent = JSON.parse(fs.readFileSync(path.join(reportDir, jsonReport!), 'utf-8'))
    const violations = reportContent.violations
    const docViolations = violations.filter((v: any) => v.source.includes('ServiceLearning.Commenting.FunctionComment'))
    expect(docViolations.length).toBeGreaterThan(0)
  })

  it('should validate variable naming convention', async () => {
    const { stdout } = await execAsync('php artisan sniffing:analyze --format=json --file=' + testFile)
    const reports = fs.readdirSync(reportDir)
    const jsonReport = reports.find(file => file.endsWith('.json'))
    const reportContent = JSON.parse(fs.readFileSync(path.join(reportDir, jsonReport!), 'utf-8'))
    const violations = reportContent.violations
    const namingViolations = violations.filter((v: any) => v.source.includes('ServiceLearning.NamingConventions.ValidVariableName'))
    expect(namingViolations.length).toBeGreaterThan(0)
  })

  it('should validate service class requirements', async () => {
    const { stdout } = await execAsync('php artisan sniffing:analyze --format=json --file=' + testFile)
    const reports = fs.readdirSync(reportDir)
    const jsonReport = reports.find(file => file.endsWith('.json'))
    const reportContent = JSON.parse(fs.readFileSync(path.join(reportDir, jsonReport!), 'utf-8'))
    const violations = reportContent.violations
    const serviceViolations = violations.filter((v: any) => v.source.includes('ServiceLearning.Classes.ServiceClass'))
    expect(serviceViolations.length).toBeGreaterThan(0)
  })

  it('should generate coverage report', async () => {
    const { stdout } = await execAsync('php artisan sniffing:analyze --format=json --file=' + testFile + ' --coverage')
    const reports = fs.readdirSync(reportDir)
    const coverageReport = reports.find(file => file.startsWith('coverage_') && file.endsWith('.json'))
    expect(coverageReport).toBeDefined()
  })

  it('should store violations in database', async () => {
    await execAsync('php artisan sniffing:analyze --format=json --file=' + testFile)
    const { stdout } = await execAsync('php artisan tinker --execute="App\\Models\\SniffViolation::count()"')
    expect(parseInt(stdout.trim())).toBeGreaterThan(0)
  })

  it('should store results in database', async () => {
    await execAsync('php artisan sniffing:analyze --format=json --file=' + testFile)
    const { stdout } = await execAsync('php artisan tinker --execute="App\\Models\\SniffResult::count()"')
    expect(parseInt(stdout.trim())).toBeGreaterThan(0)
  })
}) 