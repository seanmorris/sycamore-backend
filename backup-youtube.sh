#!/usr/bin/env bash

CHANNEL_URL=https://www.youtube.com/channel/UCbnPcIZRmY-vsJ_8RWqPz3w

cd data/local/ytdl/test_2 && {

	youtube-dl \
		--write-description \
		--write-info-json \
		--write-annotations \
		--write-thumbnail \
		--write-all-thumbnails \
		--write-sub \
		--write-auto-sub \
		$CHANNEL_URL

}
