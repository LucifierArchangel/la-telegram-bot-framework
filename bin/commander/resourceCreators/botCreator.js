const testController = require('../initProjectsContent/testControllerPHP')
const testInlineKeyboard = require('../initProjectsContent/testInlineKeyboardPHP')
const testReplyInline = require('../initProjectsContent/testReplyKeyboardPHP')
const testMessage = require('../initProjectsContent/testMessagePHP')
const testView = require('../initProjectsContent/testViewPHP')
const testBotPhp = require('../initProjectsContent/TestBotPHP')
const indexContent = require('../initProjectsContent/indexPHP')
const composerContent = require('../initProjectsContent/composerContent')
const { makeDirectory } = require('../../helpers/makeDirectory')
const { makeFile } = require('../../helpers/makeFile')

async function botCreator(name) {
    const projectStructure = [
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
                            name: `${name}Bot`,
                            sub: [
                                {
                                    type: 'dir',
                                    name: 'Controllers',
                                    sub: [
                                        {
                                            type: 'file',
                                            name: 'TestController.php',
                                            content: testController(
                                                `${name}Bot`,
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
                                                                    `${name}Bot`,
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
                                                                    `${name}Bot`,
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
                                                        `${name}Bot`,
                                                        'TestMessage'
                                                    ),
                                                },
                                            ],
                                        },
                                        {
                                            type: 'file',
                                            name: 'TestView.php',
                                            content: testView(
                                                `${name}Bot`,
                                                'TestView'
                                            ),
                                        },
                                    ],
                                },
                                {
                                    type: 'file',
                                    name: `${name}Bot.php`,
                                    content: testBotPhp(`${name}Bot`),
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
    ]

    for (const item of projectStructure) {
        if (item.type === 'dir') {
            await makeDirectory(`./`, item)
        } else if (item.type === 'file') {
            await makeFile(`./`, item)
        }
    }
}

module.exports = { botCreator }
