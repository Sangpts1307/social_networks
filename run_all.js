#!/usr/bin/env node

/**
 * Local development launcher for every service in this monorepo.
 * Stop every process started by this script with Ctrl+C.
 */
const { spawn } = require('node:child_process')
const net = require('node:net')
const path = require('node:path')

const root = __dirname
const services = [
  { name: 'Identity', port: 8004, cwd: 'services/identity-service', command: 'php', args: ['artisan', 'serve', '--host=127.0.0.1', '--port=8004', '--no-reload'] },
  { name: 'AI Knowledge', port: 8002, cwd: 'services/ai-knowledge-service', command: 'python3', args: ['-m', 'uvicorn', 'app.main:app', '--reload', '--host=127.0.0.1', '--port=8002'] },
  { name: 'Chat', port: 8003, cwd: 'services/chat-service', command: 'php', args: ['artisan', 'serve', '--host=127.0.0.1', '--port=8003', '--no-reload'] },
  { name: 'Learning', port: 8005, cwd: 'services/learning-service', command: 'php', args: ['artisan', 'serve', '--host=127.0.0.1', '--port=8005', '--no-reload'] },
  { name: 'Notification', port: 8006, cwd: 'services/notification-service', command: 'php', args: ['artisan', 'serve', '--host=127.0.0.1', '--port=8006', '--no-reload'] },
  { name: 'Social', port: 8007, cwd: 'services/social-service', command: 'php', args: ['artisan', 'serve', '--host=127.0.0.1', '--port=8007', '--no-reload'] },
  { name: 'Gateway', port: 8000, cwd: 'gateway/api-gateway', command: 'npm', args: ['run', 'start:dev'] },
  { name: 'Admin', port: 8001, cwd: 'services/admin-service', command: 'php', args: ['artisan', 'serve', '--host=127.0.0.1', '--port=8001', '--no-reload'] },
  { name: 'Web', port: 5173, cwd: 'apps/web', command: 'npm', args: ['run', 'dev', '--', '--host=127.0.0.1', '--port=5173', '--strictPort'] },
]

const children = []

function portIsInUse(port) {
  return new Promise((resolve) => {
    const socket = net.connect({ host: '127.0.0.1', port })
    socket.once('connect', () => { socket.destroy(); resolve(true) })
    socket.once('error', () => resolve(false))
  })
}

async function waitForPort(port, timeoutMs = 15000) {
  const deadline = Date.now() + timeoutMs
  while (Date.now() < deadline) {
    if (await portIsInUse(port)) return true
    await new Promise((resolve) => setTimeout(resolve, 200))
  }
  return false
}

function stop() {
  children.forEach((child) => child.kill('SIGTERM'))
  process.exit(0)
}

function run(command, args, cwd) {
  return new Promise((resolve, reject) => {
    const child = spawn(command, args, { cwd, stdio: 'inherit', shell: process.platform === 'win32' })
    child.once('error', reject)
    child.once('exit', (code) => code === 0 ? resolve() : reject(new Error(`${command} ${args.join(' ')} exited with ${code}`)))
  })
}

async function startMinio() {
  console.log('→ Chuẩn bị MinIO private cho Learning...')
  await run('docker', ['compose', 'up', '-d', 'minio', 'minio-init'], path.join(root, 'infrastructure/docker'))
  if (!await waitForPort(9000) || !await waitForPort(9001)) {
    throw new Error('MinIO không mở được cổng 9000/9001.')
  }
  console.log('✓ MinIO: http://127.0.0.1:9000 (Console: http://127.0.0.1:9001)')
}

async function main() {
  await startMinio()
  for (const service of services) {
    if (await portIsInUse(service.port)) {
      console.log(`↷ ${service.name} :${service.port} đang chạy, bỏ qua.`)
      continue
    }
    const child = spawn(service.command, service.args, {
      cwd: path.join(root, service.cwd),
      stdio: 'inherit',
      shell: process.platform === 'win32',
    })
    children.push(child)
    child.once('exit', (code) => {
      if (code && code !== 0) console.error(`✗ ${service.name} đã dừng (exit ${code}).`)
    })
    if (! await waitForPort(service.port)) {
      console.error(`✗ ${service.name} không mở được cổng ${service.port}. Xem lỗi phía trên.`)
      stop()
    }
    console.log(`✓ ${service.name}: http://127.0.0.1:${service.port}`)
  }
  console.log('\nWeb:   http://127.0.0.1:5173')
  console.log('Admin: http://127.0.0.1:8001/admin')
  console.log('Nhấn Ctrl+C để dừng các process do script này khởi động.\n')
}

process.once('SIGINT', stop)
process.once('SIGTERM', stop)
main().catch((error) => { console.error(error); stop() })
