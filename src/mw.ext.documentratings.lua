local lib = {}
local php

function lib.setupInterface()
	lib.setupInterface = nil

	-- PHP 쪽 함수 표를 가져오고 전역에서는 지운다
	php = mw_interface
	mw_interface = nil

	mw.ext = mw.ext or {}
	mw.ext.documentratings = lib
	package.loaded['mw.ext.documentratings'] = lib
end

-- 사용자가 '만 12세 이상입니다'를 체크했는지 반환한다. 익명 사용자는 항상 false.
function lib.isOver12()
	return php.isOver12()
end

return lib
