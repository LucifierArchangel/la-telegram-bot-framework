#!/usr/bin/env node

const { Command } = require('commander')

const { initProject } = require('./commander/initProject')
const { addNewResource } = require('./commander/addNewResource')

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

program
    .command('create')
    .description('Create new resource for application')
    .argument(
        '<name>',
        'Resource name: bot|controller|inline_keyboard|reply_keyboard|message|view'
    )
    .option('-b, --bot <botName>', 'Bot name to add resource')
    .requiredOption('-r, --resource-name <resourceName>', 'Resource name')
    .action(async (str, options) => {
        console.log(str, options)
        await addNewResource(str, options)
    })

program.parse()
