#!/usr/bin/env bash

cd ~/software/DeepSpeech-examples/mic_vad_streaming \
&& python3 mic_vad_streaming.py \
	--nospinner \
	-v 0 \
	-m ../models/deepspeech-0.9.3-models.pbmm \
	-s ../models/deepspeech-0.9.3-models.scorer \
	| tee ~/projects/sycamore-backend/tmp/subtitles.stream
