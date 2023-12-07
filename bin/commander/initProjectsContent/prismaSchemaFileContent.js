module.exports = `generator client {
  provider = "prisma-client-js"
  output = "./generated/db"
}

datasource db {
  provider = "mysql"
  url      = env("DATABASE_URL")
}
`
