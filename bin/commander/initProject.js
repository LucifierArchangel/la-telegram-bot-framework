const fs = require('fs/promises')
const composerContent = require('./initProjectsContent/composerContent')
const indexContent = require('./initProjectsContent/indexPHP')
const testBotPhp = require('./initProjectsContent/TestBotPHP')
const testController = require('./initProjectsContent/testControllerPHP')
const testInlineKeyboard = require('./initProjectsContent/testInlineKeyboardPHP')
const testMessage = require('./initProjectsContent/testMessagePHP')
const testReplyInline = require('./initProjectsContent/testReplyKeyboardPHP')
const testView = require('./initProjectsContent/testViewPHP')
const envFileContent = require('./initProjectsContent/envFileContent')
const prismaSchemaFileContent = require('./initProjectsContent/prismaSchemaFileContent')
const { makeDirectory } = require('../helpers/makeDirectory')
const { makeFile } = require('../helpers/makeFile')

async function initProject(name, initDb) {
    const projectStructure = [
        {
            type: 'dir',
            name: 'prisma',
            sub: [
                {
                    type: 'file',
                    name: 'schema.prisma',
                    content: prismaSchemaFileContent,
                },
            ],
        },
        {
            type: 'dir',
            name: 'src',
            sub: [
                {
                    type: 'dir',
                    name: 'Bots',
                    sub: [
                        {
                            type: 'dir',
                            name: 'TestBot',
                            sub: [
                                {
                                    type: 'dir',
                                    name: 'Controllers',
                                    sub: [
                                        {
                                            type: 'file',
                                            name: 'TestController.php',
                                            content: testController(
                                                'TestBot',
                                                'TestController'
                                            ),
                                        },
                                    ],
                                },
                                {
                                    type: 'dir',
                                    name: 'Views',
                                    sub: [
                                        {
                                            type: 'dir',
                                            name: 'Keyboards',
                                            sub: [
                                                {
                                                    type: 'dir',
                                                    name: 'Inline',
                                                    sub: [
                                                        {
                                                            type: 'file',
                                                            name: 'TestInlineKeyboard.php',
                                                            content:
                                                                testInlineKeyboard(
                                                                    'TestBot',
                                                                    'TestInlineKeyboard'
                                                                ),
                                                        },
                                                    ],
                                                },
                                                {
                                                    type: 'dir',
                                                    name: 'Reply',
                                                    sub: [
                                                        {
                                                            type: 'file',
                                                            name: 'TestReplyKeyboard.php',
                                                            content:
                                                                testReplyInline(
                                                                    'TestBot',
                                                                    'TestReplyKeyboard'
                                                                ),
                                                        },
                                                    ],
                                                },
                                            ],
                                        },
                                        {
                                            type: 'dir',
                                            name: 'Messages',
                                            sub: [
                                                {
                                                    type: 'file',
                                                    name: 'TestMessage.php',
                                                    content: testMessage(
                                                        'TestBot',
                                                        'TestMessage'
                                                    ),
                                                },
                                            ],
                                        },
                                        {
                                            type: 'file',
                                            name: 'TestView.php',
                                            content: testView(
                                                'TestBot',
                                                'TestView'
                                            ),
                                        },
                                    ],
                                },
                                {
                                    type: 'file',
                                    name: 'TestBot.php',
                                    content: testBotPhp('TestBot'),
                                },
                            ],
                        },
                    ],
                },
                {
                    type: 'file',
                    name: 'index.php',
                    content: indexContent,
                },
            ],
        },
        {
            type: 'file',
            name: '.env',
            content: envFileContent,
        },
        {
            type: 'file',
            name: 'composer.json',
            content: composerContent,
        },
    ]

    try {
        await fs.mkdir(`./${name}`)
    } catch (e) {}

    for (const item of projectStructure) {
        if (item.type === 'dir') {
            await makeDirectory(`./${name}`, item)
        } else if (item.type === 'file') {
            await makeFile(`./${name}`, item)
        }
    }
}

module.exports = {
    initProject,
}
