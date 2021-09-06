package repository

import (
	"encoding/base64"
	"strconv"
)

// DecodeCursor will decode cursor from user for mysql
func DecodeCursor(encodedId string) (int, error) {
	byt, err := base64.StdEncoding.DecodeString(encodedId)
	if err != nil {
		return 0, err
	}

	s := string(byt)
	id, err := strconv.Atoi(s)

	return id, err
}

// EncodeCursor will encode cursor from mysql to user
func EncodeCursor(id int64) string {
	sid := strconv.FormatInt(id, 10)

	return base64.StdEncoding.EncodeToString([]byte(sid))
}
