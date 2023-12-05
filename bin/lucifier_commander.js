#!/usr/bin/env node

const { Command } = require('commander')

const { initProject } = require('./commander/initProject')

program = new Command()

program
    .name('lucifier_commander')
    .description('CLI to lucifier/framework utilities')
    .version('0.0.1')

program
    .command('init')
    .description('Init new project')
    .requiredOption('-n, --name <name>', 'Name of new project')
    .option('-id, --init-db', 'Init prisma schema')
    .action(async (str, options) => {
        await initProject(str.name, str.initProject)
    })

program.parse()
