jq -c '{"id": .webpage_url_basename, title: .title, fulltitle: .fulltitle, duration: .duration, thumbnail: .thumbnail}' *.info.json | jq -s > ../../../frontend/assets/channel-video.json
