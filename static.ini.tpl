; The shared logging directory for all digistraten. No name
; conflicts will arise, because the name of the digistraat
; is part of the file name
logging.directory = "C:/tmp/nbc-medialib-test/logs"
; Will be ignored in media server component (Logging to
; standard out ("echo") is prohibited when streaming media
; files
logging.stdout = "false"
logging.level = "DEBUG"


; Connection to media DB
db0.host = "localhost"
db0.user = "root"
db0.password = ""
db0.dbname = "nbc_media_library"

baseUrl = "medialib"

debug = "false"
